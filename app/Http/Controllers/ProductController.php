<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductRequest;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Product;
use App\Models\ProductBranchPrice;
use App\Models\SaleType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    /** @var array<int, int> Opciones del selector "mostrar N por página" */
    public const PER_PAGE_OPTIONS = [20, 50, 100, 500];

    public const DEFAULT_PER_PAGE = 50;

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

        // El tamaño de pagina se toma de la lista blanca: viene del query string y
        // sin acotarlo un "per_page=999999" traeria el catalogo completo.
        $perPage = in_array((int) $request->input('per_page'), self::PER_PAGE_OPTIONS, true)
            ? (int) $request->input('per_page')
            : self::DEFAULT_PER_PAGE;

        // withQueryString: sin esto los links de paginado pierden filtros y per_page.
        $products = $query->orderBy('name')->paginate($perPage)->withQueryString();
        $departments = Department::orderBy('name')->get();

        // AJAX support: se devuelven las filas y el paginado, porque cambiar de
        // filtro o de tamaño de pagina rehace los dos.
        if ($request->ajax()) {
            return response()->json([
                'rows' => view('products.partials.table-rows', compact('products'))->render(),
                'pagination' => $products->hasPages() ? $products->links()->toHtml() : '',
            ]);
        }

        return view('products.index', compact('products', 'departments', 'perPage'));
    }

    /**
     * Show the form for creating a new product
     */
    public function create()
    {
        $this->authorize('create', Product::class);

        $departments = Department::orderBy('name')->get();
        $saleTypes = SaleType::where('is_active', true)->orderBy('name')->get();

        return view('products.create', compact('departments', 'saleTypes'));
    }

    /**
     * Store a newly created product in database
     */
    public function store(ProductRequest $request)
    {
        $this->authorize('create', Product::class);

        $defaultSaleTypeId = (int) $request->default_sale_type_id;
        $baseUnit = SaleType::findOrFail($defaultSaleTypeId)->base_unit;

        DB::beginTransaction();
        try {
            $product = Product::create([
                'department_id' => $request->department_id,
                'barcode' => $request->barcode,
                'name' => $request->name,
                'sale_type_id' => $defaultSaleTypeId,
                'unit_base' => $baseUnit,
                'min_stock' => $request->min_stock,
                'price_retail' => $request->price_retail,
                'price_wholesale' => $request->price_wholesale,
                'price_super_wholesale' => $request->price_super_wholesale,
                'cost' => $request->cost,
                'min_wholesale_qty' => $request->min_wholesale_qty,
                'min_super_wholesale_qty' => $request->min_super_wholesale_qty,
                'is_active' => $request->boolean('is_active', true),
            ]);

            $this->syncSaleTypes($product, $request);

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

        $product->load('department', 'saleType', 'productSaleTypes.saleType', 'branchPrices');
        $departments = Department::orderBy('name')->get();
        $saleTypes = SaleType::where('is_active', true)->orderBy('name')->get();

        // Sucursales activas al momento de renderizar: una sucursal nueva
        // aparece sola aqui, sin backfill ni configuracion previa.
        $branches = Branch::where('is_active', true)->orderBy('name')->get();
        // Los overrides se identifican por sucursal Y tipo de venta: un producto
        // que se vende por pza y por caja puede tener un precio distinto de la
        // caja en una sucursal sin tocar el de la pza.
        $branchPrices = $product->branchPrices->keyBy(fn ($price) => $price->branch_id . '-' . $price->sale_type_id);
        $saleTypeOptions = $product->saleTypeOptions();

        return view('products.edit', compact('product', 'departments', 'saleTypes', 'branches', 'branchPrices', 'saleTypeOptions'));
    }

    /**
     * Update the specified product in database
     */
    public function update(ProductRequest $request, Product $product)
    {
        $this->authorize('update', $product);

        $defaultSaleTypeId = (int) $request->default_sale_type_id;
        $baseUnit = SaleType::findOrFail($defaultSaleTypeId)->base_unit;

        DB::beginTransaction();
        try {
            $product->update([
                'department_id' => $request->department_id,
                'barcode' => $request->barcode,
                'name' => $request->name,
                'sale_type_id' => $defaultSaleTypeId,
                'unit_base' => $baseUnit,
                'min_stock' => $request->min_stock,
                'price_retail' => $request->price_retail,
                'price_wholesale' => $request->price_wholesale,
                'price_super_wholesale' => $request->price_super_wholesale,
                'cost' => $request->cost,
                'min_wholesale_qty' => $request->min_wholesale_qty,
                'min_super_wholesale_qty' => $request->min_super_wholesale_qty,
                'is_active' => $request->boolean('is_active', $product->is_active),
            ]);

            $this->syncSaleTypes($product, $request);

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
     * Sincronizar los tipos de venta adicionales del producto.
     *
     * El tipo principal vive en `products` (precios, umbrales y unidad base),
     * asi que aqui solo se guardan los demas marcados. Los tipos que se
     * desmarcaron se borran junto con sus precios por sucursal, que ya no
     * aplican a nada.
     */
    private function syncSaleTypes(Product $product, ProductRequest $request): void
    {
        $defaultSaleTypeId = (int) $request->default_sale_type_id;
        $checkedIds = collect($request->input('sale_type_ids', []))
            ->map(fn ($id) => (int) $id)
            ->unique();
        $extraIds = $checkedIds->reject(fn ($id) => $id === $defaultSaleTypeId);

        foreach ($extraIds as $saleTypeId) {
            $input = $request->input("sale_types.{$saleTypeId}", []);

            $product->productSaleTypes()->updateOrCreate(
                ['sale_type_id' => $saleTypeId],
                [
                    'conversion_factor' => $input['conversion_factor'] ?? 1,
                    'price_retail' => $input['price_retail'] ?? 0,
                    'price_wholesale' => $input['price_wholesale'] ?? 0,
                    'price_super_wholesale' => $input['price_super_wholesale'] ?? 0,
                    // Vacio = hereda el umbral del producto
                    'min_wholesale_qty' => ($input['min_wholesale_qty'] ?? null) ?: null,
                    'min_super_wholesale_qty' => ($input['min_super_wholesale_qty'] ?? null) ?: null,
                ]
            );
        }

        $product->productSaleTypes()
            ->whereNotIn('sale_type_id', $extraIds)
            ->delete();

        ProductBranchPrice::where('product_id', $product->id)
            ->whereNotIn('sale_type_id', $checkedIds)
            ->delete();

        $product->unsetRelation('productSaleTypes');
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
