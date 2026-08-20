<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSaleRequest;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\Sale;
use App\Services\BranchContextService;
use App\Services\SaleService;
use Illuminate\Http\Request;

class PosController extends Controller
{
    protected SaleService $saleService;
    protected BranchContextService $branchContext;

    public function __construct(SaleService $saleService, BranchContextService $branchContext)
    {
        $this->saleService = $saleService;
        $this->branchContext = $branchContext;
    }

    /**
     * Mostrar la pantalla principal del POS
     */
    public function index(Request $request)
    {
        return view('pos.index', [
            'cashRegister' => $request->get('current_cash_register'),
            'branch' => $this->branchContext->current(),
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
        $branchId = $this->branchContext->currentId();

        // Para switchers sin sucursal resuelta, buscar en todas las sucursales
        if (!$branchId && $this->branchContext->canSwitch($user)) {
            $searchAllBranches = true;
        }

        // Construir query de productos
        $productsQuery = Product::with(['department', 'saleType', 'productSaleTypes.saleType', 'branchPrices'])
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

            // Precios de la sucursal en curso: override si existe, base si no.
            // Los campos sueltos son los del tipo de venta principal; `sale_types`
            // trae todas las formas de venderlo (pza, kg, caja...) con su factor
            // de conversion hacia el stock, que siempre esta en la unidad base.
            $prices = $product->effectivePrices($branchId);
            $saleTypes = $product->saleTypeOptions($branchId);

            return [
                'id' => $product->id,
                'name' => $product->name,
                'barcode' => $product->barcode,
                'department' => $product->department->name,
                'unit' => $product->unit_base,
                'cost' => (float) $product->cost,
                'price_retail' => $prices['price_retail'],
                'price_wholesale' => $prices['price_wholesale'],
                'price_super_wholesale' => $prices['price_super_wholesale'],
                'min_wholesale_qty' => $product->min_wholesale_qty,
                'min_super_wholesale_qty' => $product->min_super_wholesale_qty,
                'stock' => $stock,
                'total_stock' => $totalStock,
                'allows_decimals' => $product->saleType->allows_decimals ?? false,
                'sale_types' => $saleTypes,
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
        $idempotencyKey = $request->input('idempotency_key');

        try {
            // Si ya se proceso una venta con esta misma clave (por ejemplo,
            // el cajero reintento tras un timeout de red), regresar esa
            // venta en vez de crear una duplicada.
            if ($idempotencyKey) {
                $existing = Sale::where('idempotency_key', $idempotencyKey)->first();
                if ($existing) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Venta procesada exitosamente',
                        'sale' => $this->saleToArray($existing),
                    ]);
                }
            }

            $sale = $this->saleService->processSale(
                $request->input('items'),
                $cashRegister,
                $request->input('client_id'),
                $request->input('payment_method', 'efectivo'),
                $idempotencyKey
            );

            return response()->json([
                'success' => true,
                'message' => 'Venta procesada exitosamente',
                'sale' => $this->saleToArray($sale),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Formatear una venta para la respuesta JSON del checkout (ticket)
     */
    private function saleToArray(Sale $sale): array
    {
        $sale->loadMissing(['items.product', 'items.saleType', 'branch', 'user']);

        return [
            'id' => $sale->id,
            'date' => $sale->created_at->format('d/m/Y H:i'),
            'branch' => $sale->branch->name,
            'cashier' => $sale->user->name,
            'payment_method' => $sale->payment_method,
            'subtotal' => (float) $sale->subtotal,
            'total' => (float) $sale->total,
            'items_count' => $sale->items->count(),
            'profit' => (float) $sale->profit,
            'items' => $sale->items->map(fn ($item) => [
                'name' => $item->product->name,
                // La unidad va en el ticket para distinguir "2 caja" de "2 pza"
                'unit' => $item->saleType->base_unit ?? $item->product->unit_base,
                'sale_type' => $item->saleType->name ?? null,
                'quantity' => (float) $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'total' => (float) $item->total,
            ]),
        ];
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
            'items.*.sale_type_id' => 'nullable|exists:sale_types,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
        ]);

        $user = auth()->user();
        $branchId = $this->branchContext->currentId();

        // Para switchers sin sucursal resuelta, validar en todas las sucursales
        $result = $this->saleService->validateStock(
            $request->input('items'),
            $branchId,
            !$branchId && $this->branchContext->canSwitch($user)
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
