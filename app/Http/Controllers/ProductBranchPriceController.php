<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Product;
use App\Models\ProductBranchPrice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductBranchPriceController extends Controller
{
    /**
     * Sincronizar los precios por sucursal de un producto.
     *
     * Solo se guarda fila para las combinaciones (sucursal, tipo de venta) que
     * traen al menos un precio. Si el usuario limpia los tres campos de una,
     * la fila se borra y esa sucursal vuelve a heredar el precio base de ese
     * tipo de venta.
     */
    public function sync(Request $request, Product $product)
    {
        $this->authorize('update', $product);

        $validated = $request->validate([
            'prices' => 'array',
            'prices.*.*.price_retail' => 'nullable|numeric|min:0',
            'prices.*.*.price_wholesale' => 'nullable|numeric|min:0',
            'prices.*.*.price_super_wholesale' => 'nullable|numeric|min:0',
        ], [
            'prices.*.*.price_retail.numeric' => 'El precio de menudeo debe ser un número',
            'prices.*.*.price_retail.min' => 'El precio de menudeo no puede ser negativo',
            'prices.*.*.price_wholesale.numeric' => 'El precio de mayoreo debe ser un número',
            'prices.*.*.price_wholesale.min' => 'El precio de mayoreo no puede ser negativo',
            'prices.*.*.price_super_wholesale.numeric' => 'El precio de super mayoreo debe ser un número',
            'prices.*.*.price_super_wholesale.min' => 'El precio de super mayoreo no puede ser negativo',
        ]);

        // Solo se aceptan sucursales activas: el formulario no ofrece otras y
        // asi un branch_id manipulado no crea filas fantasma.
        $allowedBranchIds = Branch::where('is_active', true)->pluck('id');

        // Igual que con las sucursales: solo se aceptan los tipos de venta que
        // el producto realmente maneja hoy.
        $allowedSaleTypeIds = collect($product->saleTypeOptions())->pluck('sale_type_id');

        DB::beginTransaction();
        try {
            foreach ($allowedBranchIds as $branchId) {
                foreach ($allowedSaleTypeIds as $saleTypeId) {
                    $input = $validated['prices'][$branchId][$saleTypeId] ?? [];

                    $values = [];
                    foreach (ProductBranchPrice::PRICE_FIELDS as $field) {
                        $raw = $input[$field] ?? null;
                        // '' (campo vaciado en el form) equivale a "heredar el base"
                        $values[$field] = ($raw === null || $raw === '') ? null : $raw;
                    }

                    $hasAnyPrice = collect($values)->contains(fn ($v) => $v !== null);

                    if ($hasAnyPrice) {
                        ProductBranchPrice::updateOrCreate(
                            ['product_id' => $product->id, 'branch_id' => $branchId, 'sale_type_id' => $saleTypeId],
                            $values
                        );
                    } else {
                        ProductBranchPrice::where('product_id', $product->id)
                            ->where('branch_id', $branchId)
                            ->where('sale_type_id', $saleTypeId)
                            ->delete();
                    }
                }
            }

            DB::commit();

            return redirect()
                ->route('products.edit', $product)
                ->with('success', 'Precios por sucursal actualizados exitosamente');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->with('error', 'Error al actualizar los precios por sucursal: ' . $e->getMessage());
        }
    }
}
