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
     * Show the form for creating a new inventory movement
     */
    public function create()
    {
        // Autorización usando Policy
        $this->authorize('createMovement', Inventory::class);

        $branches = Branch::where('is_active', true)->orderBy('name')->get();
        $products = Product::where('is_active', true)->orderBy('name')->get();

        return view('inventory-movements.create', compact('branches', 'products'));
    }

    /**
     * Store a newly created inventory movement in database
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
                return back()
                    ->withInput()
                    ->with('error', 'No se pueden realizar movimientos en sucursales inactivas');
            }

            // Obtener o crear el inventario
            $inventory = Inventory::firstOrCreate(
                [
                    'product_id' => $request->product_id,
                    'branch_id' => $request->branch_id,
                ],
                [
                    'stock_quantity' => 0,
                ]
            );

            // Calcular el nuevo stock según el tipo de movimiento
            $newStock = $inventory->stock_quantity;

            if ($request->type === 'IN') {
                $newStock += $request->quantity;
            } elseif ($request->type === 'OUT') {
                $newStock -= $request->quantity;
            } elseif ($request->type === 'ADJUST') {
                // ADJUST establece el stock directamente
                $newStock = $request->quantity;
            }

            // Validar que no haya stock negativo
            if ($newStock < 0) {
                return back()
                    ->withInput()
                    ->with('error', 'No se permite stock negativo. Stock actual: ' . $inventory->stock_quantity);
            }

            // Actualizar el inventario
            $inventory->update(['stock_quantity' => $newStock]);

            // Registrar el movimiento
            InventoryMovement::create([
                'product_id' => $request->product_id,
                'branch_id' => $request->branch_id,
                'user_id' => auth()->id(),
                'type' => $request->type,
                'quantity' => $request->quantity,
                'reason' => $request->reason,
            ]);

            DB::commit();

            return redirect()
                ->route('inventory-movements.index')
                ->with('success', 'Movimiento de inventario registrado exitosamente');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->with('error', 'Error al registrar el movimiento: ' . $e->getMessage());
        }
    }
}
