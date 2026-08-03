<?php

namespace App\Http\Controllers;

use App\Http\Requests\InventoryMovementRequest;
use App\Models\Branch;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryMovementController extends Controller
{
    /**
     * Display a listing of inventory movements
     */
    public function index(Request $request)
    {
        $query = InventoryMovement::with(['product', 'branch', 'user']);

        // Filter by branch
        if ($request->filled('branch')) {
            $query->where('branch_id', $request->input('branch'));
        }

        // Filter by product
        if ($request->filled('product')) {
            $query->where('product_id', $request->input('product'));
        }

        // Filter by type
        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        $movements = $query->orderBy('created_at', 'desc')->paginate(15);

        $branches = Branch::where('is_active', true)->orderBy('name')->get();
        $products = Product::where('is_active', true)->orderBy('name')->get();

        // AJAX support
        if ($request->ajax()) {
            return view('inventory-movements.partials.table-rows', compact('movements'));
        }

        return view('inventory-movements.index', compact('movements', 'branches', 'products'));
    }

    /**
     * Store a newly created inventory movement in database
     * Soporta tanto requests individuales como batch
     */
    public function store(InventoryMovementRequest $request)
    {
        // Autorización usando Policy
        $this->authorize('createMovement', Inventory::class);

        DB::beginTransaction();
        try {
            // Verificar que la sucursal esté activa
            $branch = Branch::findOrFail($request->branch_id);
            if (!$branch->is_active) {
                return $this->respondError('No se pueden realizar movimientos en sucursales inactivas', $request);
            }

            $createdMovements = [];
            $errors = [];

            // Determinar si es batch o individual
            if ($request->has('movements') && is_array($request->movements)) {
                // BATCH: Procesar múltiples movimientos
                foreach ($request->movements as $index => $movData) {
                    try {
                        $movement = $this->processMovement(
                            $request->branch_id,
                            $movData['product_id'],
                            $movData['type'],
                            $movData['quantity'],
                            $movData['reason'] ?? null
                        );
                        $createdMovements[] = $movement;
                    } catch (\Exception $e) {
                        $errors[] = "Fila " . ($index + 1) . ": " . $e->getMessage();
                    }
                }

                // Si hay errores en batch, no procesar
                if (!empty($errors)) {
                    DB::rollBack();
                    return $this->respondError(implode("\n", $errors), $request);
                }

                $message = count($createdMovements) . ' movimientos registrados exitosamente';
            } else {
                // INDIVIDUAL: Un solo movimiento
                $movement = $this->processMovement(
                    $request->branch_id,
                    $request->product_id,
                    $request->type,
                    $request->quantity,
                    $request->reason ?? null
                );
                $createdMovements[] = $movement;
                $message = 'Movimiento registrado exitosamente';
            }

            DB::commit();

            // Respuesta según tipo de request
            if ($request->ajax() || $request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'count' => count($createdMovements),
                    'movements' => collect($createdMovements)
                        ->map(fn($m) => $m->load('product', 'branch', 'user'))
                        ->toArray()
                ]);
            }

            return redirect()
                ->route('inventory-movements.index')
                ->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->respondError('Error al registrar el movimiento: ' . $e->getMessage(), $request);
        }
    }

    /**
     * Buscar productos para autocomplete
     * GET /inventory-movements/products/search?query=xxx&branch_id=1
     */
    public function searchProducts(Request $request)
    {
        $request->validate([
            'query' => 'required|string|min:1',
            'branch_id' => 'required|exists:branches,id',
        ]);

        $query = $request->input('query');
        $branchId = $request->input('branch_id');

        // Construir query de productos
        $products = Product::where('is_active', true)
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('barcode', 'like', "%{$query}%");
            })
            ->limit(20)
            ->get();

        // Agregar información de stock
        $results = $products->map(function ($product) use ($branchId) {
            $inventory = Inventory::where('product_id', $product->id)
                ->where('branch_id', $branchId)
                ->first();
            $stock = $inventory ? (float) $inventory->stock_quantity : 0;

            return [
                'id' => $product->id,
                'name' => $product->name,
                'barcode' => $product->barcode,
                'stock' => $stock,
            ];
        });

        return response()->json([
            'success' => true,
            'products' => $results,
            'count' => $results->count(),
        ]);
    }

    /**
     * Procesar un movimiento individual de inventario
     * Se extrae a método helper para reutilizarlo en batch
     */
    private function processMovement($branchId, $productId, $type, $quantity, $reason)
    {
        // Obtener o crear el inventario
        $inventory = Inventory::firstOrCreate(
            [
                'product_id' => $productId,
                'branch_id' => $branchId,
            ],
            [
                'stock_quantity' => 0,
            ]
        );

        // Calcular el nuevo stock según el tipo de movimiento
        $newStock = $inventory->stock_quantity;

        if ($type === 'IN') {
            $newStock += $quantity;
        } elseif ($type === 'OUT') {
            $newStock -= $quantity;
        } elseif ($type === 'ADJUST') {
            $newStock = $quantity;
        }

        // Validar que no haya stock negativo
        if ($newStock < 0) {
            throw new \Exception('Stock insuficiente. Stock actual: ' . $inventory->stock_quantity);
        }

        // Actualizar el inventario
        $inventory->update(['stock_quantity' => $newStock]);

        // Registrar el movimiento
        return InventoryMovement::create([
            'product_id' => $productId,
            'branch_id' => $branchId,
            'user_id' => auth()->id(),
            'type' => $type,
            'quantity' => $quantity,
            'reason' => $reason,
        ]);
    }

    /**
     * Responder con error dependiendo del tipo de request
     */
    private function respondError($message, $request)
    {
        if ($request->ajax() || $request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => $message,
            ], 422);
        }

        return back()
            ->withInput()
            ->with('error', $message);
    }
}
