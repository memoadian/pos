<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductMinimumController extends Controller
{
    /**
     * Pantalla para editar en lote la cantidad mínima de mayoreo/súper
     * mayoreo de varios productos sin abrir la edición de cada uno.
     */
    public function edit(Request $request)
    {
        $this->authorize('manageMinimums', Product::class);

        $query = Product::where('is_active', true);

        if ($request->filled('search')) {
            $query->search($request->input('search'));
        }

        if ($request->filled('department')) {
            $query->where('department_id', $request->input('department'));
        }

        $perPage = in_array((int) $request->input('per_page'), ProductController::PER_PAGE_OPTIONS, true)
            ? (int) $request->input('per_page')
            : ProductController::DEFAULT_PER_PAGE;

        $products = $query->orderBy('name')->paginate($perPage)->withQueryString();
        $departments = Department::orderBy('name')->get();

        if ($request->ajax()) {
            return response()->json([
                'rows' => view('products.partials.minimum-rows', compact('products'))->render(),
                'pagination' => $products->hasPages() ? $products->links()->toHtml() : '',
            ]);
        }

        return view('products.minimums', compact('products', 'departments', 'perPage'));
    }

    /**
     * Guarda los mínimos editados de los productos de la página actual. Es
     * todo o nada: si una fila no cumple la regla (súper mayoreo > mayoreo),
     * no se guarda ninguna, para no dejar el lote a medias.
     */
    public function update(Request $request)
    {
        $this->authorize('manageMinimums', Product::class);

        $validated = $request->validate([
            'products' => 'required|array',
            'products.*.min_wholesale_qty' => 'nullable|integer|min:1',
            'products.*.min_super_wholesale_qty' => 'nullable|integer|min:1',
        ], [
            'products.*.min_wholesale_qty.integer' => 'La cantidad mínima de mayoreo debe ser un número entero',
            'products.*.min_wholesale_qty.min' => 'La cantidad mínima de mayoreo debe ser 1 o más',
            'products.*.min_super_wholesale_qty.integer' => 'La cantidad mínima de súper mayoreo debe ser un número entero',
            'products.*.min_super_wholesale_qty.min' => 'La cantidad mínima de súper mayoreo debe ser 1 o más',
        ]);

        $products = Product::whereIn('id', array_keys($validated['products']))->get()->keyBy('id');

        $errors = [];
        foreach ($validated['products'] as $productId => $fields) {
            $product = $products->get($productId);
            if (! $product) {
                continue;
            }

            $wholesaleQty = $fields['min_wholesale_qty'] ?? null;
            $superWholesaleQty = $fields['min_super_wholesale_qty'] ?? null;

            if ($wholesaleQty && $superWholesaleQty && $superWholesaleQty <= $wholesaleQty) {
                $errors[] = "\"{$product->name}\": el mínimo de súper mayoreo ({$superWholesaleQty}) debe ser mayor que el de mayoreo ({$wholesaleQty})";
            }
        }

        if (! empty($errors)) {
            return back()->withInput()->with('error', 'No se guardó ningún cambio. Corrige: '.implode('; ', $errors));
        }

        foreach ($validated['products'] as $productId => $fields) {
            $product = $products->get($productId);
            if (! $product) {
                continue;
            }

            $product->update([
                'min_wholesale_qty' => $fields['min_wholesale_qty'] ?? null,
                'min_super_wholesale_qty' => $fields['min_super_wholesale_qty'] ?? null,
            ]);
        }

        return redirect()
            ->route('products.minimums.edit', $request->only(['search', 'department', 'per_page', 'page']))
            ->with('success', 'Mínimos actualizados: '.count($validated['products']).' producto(s).');
    }
}
