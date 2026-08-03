<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSaleRequest;
use App\Models\Inventory;
use App\Models\Product;
use App\Services\SaleService;
use Illuminate\Http\Request;

class PosController extends Controller
{
    protected SaleService $saleService;

    public function __construct(SaleService $saleService)
    {
        $this->saleService = $saleService;
    }

    /**
     * Mostrar la pantalla principal del POS
     */
    public function index(Request $request)
    {
        $cashRegister = $request->get('current_cash_register');
        $user = auth()->user();

        // Admins sin caja abierta pueden ver el POS en modo demo
        $branch = $user->branch;
        if (!$cashRegister && $user->hasRole(['Admin', 'Admin'])) {
            // Usar la primera sucursal activa si no tiene asignada
            if (!$branch) {
                $branch = \App\Models\Branch::where('is_active', true)->first();
            }
        }

        if (!$branch) {
            return redirect()->route('dashboard')
                ->with('error', 'No hay sucursales configuradas. Crea una sucursal para usar el POS.');
        }

        return view('pos.index', [
            'cashRegister' => $cashRegister,
            'branch' => $branch,
            'isDemo' => !$cashRegister && $user->hasRole(['Admin', 'Admin']),
        ]);
    }

    /**
     * Buscar productos (AJAX)
     * GET /pos/products/search?query=xxx&all_branches=0
     */
    public function searchProducts(Request $request)
    {
        $request->validate([
            'query' => 'required|string|min:1',
            'all_branches' => 'nullable|boolean',
        ]);

        $user = auth()->user();
        $query = $request->input('query');
        $searchAllBranches = $request->boolean('all_branches', false);
        $branchId = $user->branch_id;

        // Para admins sin branch, buscar en todas las sucursales
        if (!$branchId && $user->hasRole(['Admin', 'Admin'])) {
            $searchAllBranches = true;
        }

        // Construir query de productos
        $productsQuery = Product::with(['department', 'saleType'])
            ->where('is_active', true)
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('barcode', 'like', "%{$query}%");
            });

        // Obtener productos con límite
        $products = $productsQuery->limit(20)->get();

        // Agregar información de stock y precios
        $results = $products->map(function ($product) use ($branchId, $searchAllBranches) {
            $stock = 0;
            if ($branchId) {
                $inventory = Inventory::where('product_id', $product->id)
                    ->where('branch_id', $branchId)
                    ->first();
                $stock = $inventory ? (float) $inventory->stock_quantity : 0;
            }

            // Si busca en todas las sucursales, obtener stock total
            $totalStock = $stock;
            if ($searchAllBranches) {
                $totalStock = (float) Inventory::where('product_id', $product->id)
                    ->sum('stock_quantity');
            }

            return [
                'id' => $product->id,
                'name' => $product->name,
                'barcode' => $product->barcode,
                'department' => $product->department->name,
                'unit' => $product->unit_base,
                'cost' => (float) $product->cost,
                'price_retail' => (float) $product->price_retail,
                'price_wholesale' => (float) $product->price_wholesale,
                'price_super_wholesale' => (float) $product->price_super_wholesale,
                'min_wholesale_qty' => $product->min_wholesale_qty,
                'min_super_wholesale_qty' => $product->min_super_wholesale_qty,
                'stock' => $stock,
                'total_stock' => $totalStock,
                'allows_decimals' => $product->saleType->allows_decimals ?? false,
                'stock_status' => $this->getStockStatus($stock),
            ];
        });

        return response()->json([
            'success' => true,
            'products' => $results,
            'count' => $results->count(),
        ]);
    }

    /**
     * Procesar venta (AJAX)
     * POST /pos/checkout
     */
    public function checkout(StoreSaleRequest $request)
    {
        $cashRegister = $request->get('current_cash_register');

        try {
            $sale = $this->saleService->processSale(
                $request->input('items'),
                $cashRegister,
                $request->input('client_id'),
                $request->input('payment_method', 'efectivo')
            );

            return response()->json([
                'success' => true,
                'message' => 'Venta procesada exitosamente',
                'sale' => [
                    'id' => $sale->id,
                    'total' => (float) $sale->total,
                    'items_count' => $sale->items->count(),
                    'profit' => (float) $sale->profit,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Validar stock en tiempo real (AJAX)
     * POST /pos/validate-stock
     */
    public function validateStock(Request $request)
    {
        $request->validate([
            'items' => 'required|array',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
        ]);

        $user = auth()->user();
        $branchId = $user->branch_id;

        // Para admins sin branch, validar en todas las sucursales
        $result = $this->saleService->validateStock(
            $request->input('items'),
            $branchId,
            !$branchId && $user->hasRole(['Admin', 'Admin'])
        );

        return response()->json([
            'success' => true,
            'all_valid' => $result['all_valid'],
            'items' => $result['items'],
        ]);
    }

    /**
     * Obtener estado visual del stock
     */
    private function getStockStatus(float $stock): string
    {
        if ($stock <= 0) {
            return 'out_of_stock';
        }
        if ($stock <= 5) {
            return 'low_stock';
        }
        return 'in_stock';
    }
}
