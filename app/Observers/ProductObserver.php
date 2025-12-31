<?php

namespace App\Observers;

use App\Models\Branch;
use App\Models\Inventory;
use App\Models\Product;

class ProductObserver
{
    /**
     * Handle the Product "created" event.
     */
    public function created(Product $product): void
    {
        // Crear un registro de inventario para cada sucursal activa
        $branches = Branch::where('is_active', true)->get();

        foreach ($branches as $branch) {
            Inventory::create([
                'product_id' => $product->id,
                'branch_id' => $branch->id,
                'stock_quantity' => 0,
            ]);
        }
    }
}
