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
     * @param array $items Array de items con product_id, quantity, unit_price
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
        string $paymentMethod = 'efectivo'
    ): Sale {
        return DB::transaction(function () use ($items, $cashRegister, $clientId, $paymentMethod) {
            $subtotal = 0;
            $totalCost = 0;
            $saleItems = [];

            // Validar y preparar items
            foreach ($items as $item) {
                // Bloquear el registro de inventario para evitar condiciones de carrera
                $inventory = Inventory::where('product_id', $item['product_id'])
                    ->where('branch_id', $cashRegister->branch_id)
                    ->lockForUpdate()
                    ->first();

                if (!$inventory) {
                    $product = Product::find($item['product_id']);
                    throw new Exception("No hay inventario para el producto: " . ($product->name ?? 'ID ' . $item['product_id']));
                }

                if ($inventory->stock_quantity < $item['quantity']) {
                    $product = $inventory->product;
                    throw new Exception("Stock insuficiente para '{$product->name}'. Disponible: {$inventory->stock_quantity}, Solicitado: {$item['quantity']}");
                }

                $product = $inventory->product;

                if (!$product->is_active) {
                    throw new Exception("El producto '{$product->name}' está inactivo");
                }

                $itemTotal = $item['quantity'] * $item['unit_price'];
                $itemCost = $item['quantity'] * $product->cost;

                $subtotal += $itemTotal;
                $totalCost += $itemCost;

                $saleItems[] = [
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'cost' => $product->cost,
                    'total' => $itemTotal,
                    'inventory' => $inventory,
                    'product' => $product,
                ];
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
            ]);

            // Crear items y descontar inventario
            foreach ($saleItems as $item) {
                // Crear item de venta
                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'cost' => $item['cost'],
                    'total' => $item['total'],
                ]);

                // Descontar inventario
                $item['inventory']->decrement('stock_quantity', $item['quantity']);

                // Registrar movimiento de inventario
                InventoryMovement::create([
                    'product_id' => $item['product_id'],
                    'branch_id' => $cashRegister->branch_id,
                    'user_id' => auth()->id(),
                    'type' => 'OUT',
                    'quantity' => $item['quantity'],
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

        foreach ($items as $item) {
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
                'available' => $available,
                'valid' => $available >= $requested,
            ];
        }

        return [
            'all_valid' => collect($results)->every('valid'),
            'items' => $results,
        ];
    }
}
