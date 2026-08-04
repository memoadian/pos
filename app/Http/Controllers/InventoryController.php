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

        // Filter by low stock: compara contra el mínimo definido en cada producto
        if ($request->boolean('low_stock')) {
            $query->join('products', 'products.id', '=', 'inventories.product_id')
                ->whereNotNull('products.min_stock')
                ->whereColumn('inventories.stock_quantity', '<=', 'products.min_stock');
        }

        // Search by product name or barcode
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->whereHas('product', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('barcode', 'like', "%{$search}%");
            });
        }

        $inventories = $query->orderBy('stock_quantity', 'asc')->paginate(15);

        $departments = Department::orderBy('name')->get();
        $products = Product::where('is_active', true)->orderBy('name')->get();

        // AJAX support
        if ($request->ajax()) {
            return view('inventory.partials.table-rows', compact('inventories'));
        }

        return view('inventory.index', compact('inventories', 'departments', 'products'));
    }
}
