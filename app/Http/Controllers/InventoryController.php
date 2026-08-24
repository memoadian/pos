<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Inventory;
use App\Models\Product;
use App\Services\BranchContextService;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function __construct(protected BranchContextService $branchContext)
    {
    }

    /**
     * Display inventory by branch
     */
    public function index(Request $request)
    {
        // Autorización usando Policy
        $this->authorize('viewAny', Inventory::class);

        $query = Inventory::with(['product.department', 'branch'])->select('inventories.*');

        // Siempre se consulta la sucursal activa del usuario (seleccionada en el header)
        $branchId = $this->branchContext->currentId();
        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        // Filter by department
        if ($request->filled('department')) {
            $query->whereHas('product', function($q) use ($request) {
                $q->where('department_id', $request->input('department'));
            });
        }

        // Filter by product
        if ($request->filled('product')) {
            $query->where('product_id', $request->input('product'));
        }

        // Filter by in stock: el alta de un producto crea su fila de inventario en
        // cero en cada sucursal, asi que sin este filtro la pantalla es casi toda ceros.
        if ($request->boolean('in_stock')) {
            $query->where('inventories.stock_quantity', '>', 0);
        }

        // Filter by low stock: compara contra el mínimo definido en cada producto
        if ($request->boolean('low_stock')) {
            $query->join('products', 'products.id', '=', 'inventories.product_id')
                ->whereNotNull('products.min_stock')
                ->whereColumn('inventories.stock_quantity', '<=', 'products.min_stock');
        }

        // Search by product name or barcode
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->whereHas('product', fn ($q) => $q->search($search));
        }

        // withQueryString: sin esto los links de paginado pierden los filtros.
        $inventories = $query->orderBy('stock_quantity', 'asc')->paginate(15)->withQueryString();

        // Total sin los filtros del usuario, pero sí de la sucursal activa: la
        // pantalla siempre habla de una sola sucursal.
        $totalInventories = Inventory::when($branchId, fn ($q) => $q->where('branch_id', $branchId))->count();

        $summary = view('components.table-summary', [
            'paginator' => $inventories,
            'total' => $totalInventories,
            'singular' => 'producto',
            'plural' => 'productos',
            'icon' => 'bi-boxes',
        ])->render();

        $departments = Department::orderBy('name')->get();
        $products = Product::where('is_active', true)->orderBy('name')->get();

        // AJAX support: se devuelven las filas, el paginado y el resumen, porque
        // filtrar rehace los tres.
        if ($request->ajax()) {
            return response()->json([
                'rows' => view('inventory.partials.table-rows', compact('inventories'))->render(),
                'pagination' => $inventories->hasPages() ? $inventories->links()->toHtml() : '',
                'summary' => $summary,
            ]);
        }

        return view('inventory.index', compact('inventories', 'departments', 'products', 'totalInventories', 'summary'));
    }
}
