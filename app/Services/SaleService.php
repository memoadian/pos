<?php

namespace App\Services;

use App\Models\CashRegister;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use Exception;
use Illuminate\Support\Facades\DB;

class SaleService
{
    /**
     * Procesar una venta completa con transacción
     *
     * @param  array  $items  Array de items con product_id, quantity, unit_price y sale_type_id opcional
     * @param  CashRegister  $cashRegister  La caja registradora activa
     * @param  int|null  $clientId  ID del cliente (opcional)
     * @param  string  $paymentMethod  Método de pago
     * @param  bool  $offline  Venta cobrada sin conexion por el POS offline y
     *                         reenviada al reconectar (ver resources/js/pos/sync.js): ya
     *                         ocurrio fisicamente, asi que nunca se rechaza por falta de
     *                         stock, solo se marca (`stock_issue`) para que un admin la revise.
     * @param  string|null  $soldAt  Hora real del cobro segun el cliente
     *                               (created_at sigue siendo la hora en que el servidor la recibio)
     * @param  string|null  $offlineRef  Folio provisional impreso en el ticket
     *
     * @throws Exception
     */
    public function processSale(
        array $items,
        CashRegister $cashRegister,
        ?int $clientId = null,
        string $paymentMethod = 'efectivo',
        ?string $idempotencyKey = null,
        bool $offline = false,
        ?string $soldAt = null,
        ?string $offlineRef = null
    ): Sale {
        return DB::transaction(function () use ($items, $cashRegister, $clientId, $paymentMethod, $idempotencyKey, $offline, $soldAt, $offlineRef) {
            $subtotal = 0;
            $totalCost = 0;
            $saleItems = [];
            $stockIssue = false;

            // El mismo producto puede venir en varias partidas (ej. 2 pza y 1
            // caja): se agrupan para bloquear una sola vez su inventario y
            // comparar el stock contra el total en unidades base.
            $itemsByProduct = collect($items)->groupBy('product_id');

            foreach ($itemsByProduct as $productId => $productItems) {
                // Bloquear el registro de inventario para evitar condiciones de carrera
                $inventory = Inventory::where('product_id', $productId)
                    ->where('branch_id', $cashRegister->branch_id)
                    ->lockForUpdate()
                    ->first();

                if (! $inventory) {
                    $product = Product::find($productId);

                    if (! $offline) {
                        throw new Exception('No hay inventario para el producto: '.($product->name ?? 'ID '.$productId));
                    }

                    // Venta offline de un producto sin registro de inventario
                    // en esta sucursal: se crea en cero en vez de perder la
                    // venta, y el faltante se refleja abajo como stock_issue.
                    $inventory = Inventory::create([
                        'product_id' => $productId,
                        'branch_id' => $cashRegister->branch_id,
                        'stock_quantity' => 0,
                    ]);
                }

                $product = $inventory->product;

                if (! $product->is_active) {
                    throw new Exception("El producto '{$product->name}' está inactivo");
                }

                $baseQuantity = 0;

                foreach ($productItems as $item) {
                    $saleTypeId = isset($item['sale_type_id']) ? (int) $item['sale_type_id'] : null;
                    $option = $product->resolveSaleTypeOption($saleTypeId);

                    if (! $option) {
                        throw new Exception("El tipo de venta seleccionado no aplica para '{$product->name}'");
                    }

                    // Lo que se descuenta del inventario siempre va en la unidad
                    // base: una caja de 24 pza descuenta 24.
                    $factor = (float) $option['conversion_factor'];
                    $itemBaseQuantity = $item['quantity'] * $factor;
                    $baseQuantity += $itemBaseQuantity;

                    $itemTotal = $item['quantity'] * $item['unit_price'];
                    // El costo de una unidad del tipo es el del producto por su factor
                    $itemUnitCost = $factor * $product->cost;

                    $subtotal += $itemTotal;
                    $totalCost += $item['quantity'] * $itemUnitCost;

                    $saleItems[] = [
                        'product_id' => $product->id,
                        'sale_type_id' => $option['sale_type_id'],
                        'conversion_factor' => $factor,
                        'quantity' => $item['quantity'],
                        'base_quantity' => $itemBaseQuantity,
                        'unit_price' => $item['unit_price'],
                        'cost' => $itemUnitCost,
                        'total' => $itemTotal,
                        'inventory' => $inventory,
                        'product' => $product,
                    ];
                }

                if ($inventory->stock_quantity < $baseQuantity) {
                    if (! $offline) {
                        throw new Exception("Stock insuficiente para '{$product->name}'. Disponible: {$inventory->stock_quantity}, Solicitado: {$baseQuantity}");
                    }

                    // La venta ya se cobro sin conexion: se acepta con stock
                    // en negativo (Inventory::decrement no dispara el guard
                    // de "saving" que evita negativos en updates normales) y
                    // se deja marcada para que un admin la revise.
                    $stockIssue = true;
                }
            }

            $profit = $subtotal - $totalCost;

            // Crear la venta
            $sale = Sale::create([
                'branch_id' => $cashRegister->branch_id,
                'cash_register_id' => $cashRegister->id,
                'user_id' => auth()->id(),
                'client_id' => $clientId,
                'subtotal' => $subtotal,
                'total' => $subtotal, // Por ahora sin impuestos
                'profit' => $profit,
                'payment_method' => $paymentMethod,
                'idempotency_key' => $idempotencyKey,
                'sold_at' => $soldAt,
                'offline_ref' => $offlineRef,
                'stock_issue' => $stockIssue,
            ]);

            // Crear items y descontar inventario
            foreach ($saleItems as $item) {
                // Crear item de venta
                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $item['product_id'],
                    'sale_type_id' => $item['sale_type_id'],
                    'quantity' => $item['quantity'],
                    'conversion_factor' => $item['conversion_factor'],
                    'unit_price' => $item['unit_price'],
                    'cost' => $item['cost'],
                    'total' => $item['total'],
                ]);

                // Descontar inventario (en unidad base)
                $item['inventory']->decrement('stock_quantity', $item['base_quantity']);

                // Registrar movimiento de inventario
                InventoryMovement::create([
                    'product_id' => $item['product_id'],
                    'branch_id' => $cashRegister->branch_id,
                    'user_id' => auth()->id(),
                    'type' => 'OUT',
                    'quantity' => $item['base_quantity'],
                    'reason' => "SALE - Venta #{$sale->id}",
                ]);
            }

            // Actualizar totales de la caja
            $cashRegister->increment('total_sales', $subtotal);
            $cashRegister->increment('total_profit', $profit);

            // Actualizar contadores por método de pago
            switch ($paymentMethod) {
                case 'efectivo':
                    $cashRegister->increment('cash_sales', $subtotal);
                    break;
                case 'tarjeta':
                    $cashRegister->increment('card_sales', $subtotal);
                    break;
                case 'transferencia':
                    $cashRegister->increment('transfer_sales', $subtotal);
                    break;
            }

            return $sale->load('items.product');
        });
    }

    /**
     * Cancelar una venta completa: revierte inventario y los totales de la caja
     * (aunque la caja ya esté cerrada) y deja registro de quién y por qué.
     *
     * @throws Exception
     */
    public function cancelSale(Sale $sale, ?string $reason, int $userId): Sale
    {
        return DB::transaction(function () use ($sale, $reason, $userId) {
            $sale->loadMissing('items');

            if ($sale->isCancelled()) {
                throw new Exception('La venta ya fue cancelada.');
            }

            // Devolver el inventario en unidad base (espejo de processSale)
            foreach ($sale->items as $item) {
                $baseQuantity = (float) $item->quantity * (float) $item->conversion_factor;

                $inventory = Inventory::where('product_id', $item->product_id)
                    ->where('branch_id', $sale->branch_id)
                    ->lockForUpdate()
                    ->first();

                if ($inventory) {
                    $inventory->increment('stock_quantity', $baseQuantity);
                } else {
                    Inventory::create([
                        'product_id' => $item->product_id,
                        'branch_id' => $sale->branch_id,
                        'stock_quantity' => $baseQuantity,
                    ]);
                }

                InventoryMovement::create([
                    'product_id' => $item->product_id,
                    'branch_id' => $sale->branch_id,
                    'user_id' => $userId,
                    'type' => 'IN',
                    'quantity' => $baseQuantity,
                    'reason' => "CANCEL - Cancelación venta #{$sale->id}",
                ]);
            }

            // Revertir los totales de la caja (misma lógica que processSale, en negativo)
            $cashRegister = $sale->cashRegister;
            $cashRegister->decrement('total_sales', $sale->subtotal);
            $cashRegister->decrement('total_profit', $sale->profit);

            switch ($sale->payment_method) {
                case 'efectivo':
                    $cashRegister->decrement('cash_sales', $sale->subtotal);
                    break;
                case 'tarjeta':
                    $cashRegister->decrement('card_sales', $sale->subtotal);
                    break;
                case 'transferencia':
                    $cashRegister->decrement('transfer_sales', $sale->subtotal);
                    break;
            }

            $sale->update([
                'status' => 'cancelada',
                'cancelled_at' => now(),
                'cancelled_by' => $userId,
                'cancellation_reason' => $reason,
            ]);

            return $sale;
        });
    }

    /**
     * Validar stock para una lista de items
     *
     * @param  bool  $allBranches  Buscar en todas las sucursales
     */
    public function validateStock(array $items, ?int $branchId = null, bool $allBranches = false): array
    {
        $results = [];

        // Varias partidas del mismo producto (pza y caja, por ejemplo) comparten
        // el mismo stock, asi que se validan sumadas y en unidad base.
        $requestedByProduct = [];
        $factors = [];

        foreach ($items as $index => $item) {
            $product = Product::find($item['product_id']);
            $saleTypeId = isset($item['sale_type_id']) ? (int) $item['sale_type_id'] : null;
            $option = $product?->resolveSaleTypeOption($saleTypeId);
            $factor = (float) ($option['conversion_factor'] ?? 1);

            $factors[$index] = $factor;
            $requestedByProduct[$item['product_id']] = ($requestedByProduct[$item['product_id']] ?? 0)
                + ((float) $item['quantity'] * $factor);
        }

        foreach ($items as $index => $item) {
            if ($allBranches || ! $branchId) {
                // Obtener stock total de todas las sucursales
                $available = (float) Inventory::where('product_id', $item['product_id'])
                    ->sum('stock_quantity');
            } else {
                // Obtener stock de una sucursal específica
                $inventory = Inventory::where('product_id', $item['product_id'])
                    ->where('branch_id', $branchId)
                    ->first();
                $available = $inventory ? (float) $inventory->stock_quantity : 0;
            }

            $requested = (float) $item['quantity'];

            $results[] = [
                'product_id' => $item['product_id'],
                'requested' => $requested,
                'requested_base' => $requested * $factors[$index],
                'available' => $available,
                'valid' => $available >= $requestedByProduct[$item['product_id']],
            ];
        }

        return [
            'all_valid' => collect($results)->every('valid'),
            'items' => $results,
        ];
    }
}
