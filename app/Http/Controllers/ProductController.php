<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductRequest;
use App\Models\Department;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    /**
     * Display a listing of products
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Product::class);

        $query = Product::with('department');

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('barcode', 'like', "%{$search}%");
            });
        }

        // Filter by department
        if ($request->filled('department')) {
            $query->where('department_id', $request->input('department'));
        }

        // Filter by active status
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->input('is_active'));
        }

        $products = $query->orderBy('name')->paginate(15);
        $departments = Department::orderBy('name')->get();

        // AJAX support
        if ($request->ajax()) {
            return view('products.partials.table-rows', compact('products'));
        }

        return view('products.index', compact('products', 'departments'));
    }

    /**
     * Show the form for creating a new product
     */
    public function create()
    {
        $this->authorize('create', Product::class);

        $departments = Department::orderBy('name')->get();

        return view('products.create', compact('departments'));
    }

    /**
     * Store a newly created product in database
     */
    public function store(ProductRequest $request)
    {
        $this->authorize('create', Product::class);

        DB::beginTransaction();
        try {
            Product::create([
                'department_id' => $request->department_id,
                'barcode' => $request->barcode,
                'name' => $request->name,
                'sale_type' => $request->sale_type,
                'unit_base' => $request->unit_base,
                'price_retail' => $request->price_retail,
                'price_wholesale' => $request->price_wholesale,
                'price_super_wholesale' => $request->price_super_wholesale,
                'cost' => $request->cost,
                'is_active' => $request->boolean('is_active', true),
            ]);

            DB::commit();

            return redirect()
                ->route('products.index')
                ->with('success', 'Producto creado exitosamente');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->with('error', 'Error al crear el producto: ' . $e->getMessage());
        }
    }

    /**
     * Show the form for editing the specified product
     */
    public function edit(Product $product)
    {
        $this->authorize('update', $product);

        $product->load('department');
        $departments = Department::orderBy('name')->get();

        return view('products.edit', compact('product', 'departments'));
    }

    /**
     * Update the specified product in database
     */
    public function update(ProductRequest $request, Product $product)
    {
        $this->authorize('update', $product);

        DB::beginTransaction();
        try {
            $product->update([
                'department_id' => $request->department_id,
                'barcode' => $request->barcode,
                'name' => $request->name,
                'sale_type' => $request->sale_type,
                'unit_base' => $request->unit_base,
                'price_retail' => $request->price_retail,
                'price_wholesale' => $request->price_wholesale,
                'price_super_wholesale' => $request->price_super_wholesale,
                'cost' => $request->cost,
                'is_active' => $request->boolean('is_active', $product->is_active),
            ]);

            DB::commit();

            return redirect()
                ->route('products.index')
                ->with('success', 'Producto actualizado exitosamente');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->with('error', 'Error al actualizar el producto: ' . $e->getMessage());
        }
    }

    /**
     * Deactivate the specified product (soft delete)
     */
    public function destroy(Product $product)
    {
        $this->authorize('delete', $product);

        // Verificar si tiene movimientos de inventario
        if ($product->inventoryMovements()->count() > 0) {
            return redirect()
                ->route('products.index')
                ->with('error', 'No se puede eliminar un producto con movimientos de inventario registrados. Solo se puede desactivar.');
        }

        // Verificar si tiene ventas
        if ($product->saleItems()->count() > 0) {
            return redirect()
                ->route('products.index')
                ->with('error', 'No se puede eliminar un producto con ventas registradas. Solo se puede desactivar.');
        }

        // Si no tiene movimientos ni ventas, solo desactivar
        $product->update(['is_active' => false]);

        return redirect()
            ->route('products.index')
            ->with('success', 'Producto desactivado exitosamente');
    }
}
