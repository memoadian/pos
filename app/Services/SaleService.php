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
     * @param array $items Array de items con product_id, quantity, unit_price y sale_type_id opcional
     * @param CashRegister $cashRegister La caja registradora activa
     * @param int|null $clientId ID del cliente (opcional)
     * @param string $paymentMethod Método de pago
     * @return Sale
     * @throws Exception
     */
    public function processSale(
        array $items,
        CashRegister $cashRegister,
        ?int $clientId = null,
        string $paymentMethod = 'efectivo',
        ?string $idempotencyKey = null
    ): Sale {
        return DB::transaction(function () use ($items, $cashRegister, $clientId, $paymentMethod, $idempotencyKey) {
            $subtotal = 0;
            $totalCost = 0;
            $saleItems = [];

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

                if (!$inventory) {
                    $product = Product::find($productId);
                    throw new Exception("No hay inventario para el producto: " . ($product->name ?? 'ID ' . $productId));
                }

                $product = $inventory->product;

                if (!$product->is_active) {
                    throw new Exception("El producto '{$product->name}' está inactivo");
                }

                $baseQuantity = 0;

                foreach ($productItems as $item) {
                    $saleTypeId = isset($item['sale_type_id']) ? (int) $item['sale_type_id'] : null;
                    $option = $product->resolveSaleTypeOption($saleTypeId);

                    if (!$option) {
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
                    throw new Exception("Stock insuficiente para '{$product->name}'. Disponible: {$inventory->stock_quantity}, Solicitado: {$baseQuantity}");
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
     * Validar stock para una lista de items
     *
     * @param array $items
     * @param int|null $branchId
     * @param bool $allBranches Buscar en todas las sucursales
     * @return array
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
            if ($allBranches || !$branchId) {
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
