<?php

namespace App\Http\Controllers;

use App\Http\Requests\CashRegisterMovementRequest;
use App\Http\Requests\CloseCashRegisterRequest;
use App\Http\Requests\OpenCashRegisterRequest;
use App\Models\CashRegister;
use App\Models\CashRegisterMovement;
use App\Services\BranchContextService;
use App\Services\CashRegisterService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CashRegisterController extends Controller
{
    public function __construct(protected BranchContextService $branchContext)
    {
    }

    /**
     * Ver estado de caja actual
     */
    public function index()
    {
        $user = auth()->user();
        $branch = $this->branchContext->current();

        // Sin sucursal resuelta: vista vacía con opción de ir al historial
        if (!$branch) {
            return redirect()->route('dashboard')
                ->with('error', 'No tienes una sucursal asignada.');
        }

        $openRegister = CashRegister::with(['branch', 'sales.items', 'expenses.user'])
            ->where('user_id', $user->id)
            ->where('branch_id', $branch->id)
            ->where('status', 'abierta')
            ->first();

        $recentRegisters = CashRegister::with('branch')
            ->where('user_id', $user->id)
            ->where('status', 'cerrada')
            ->orderBy('closed_at', 'desc')
            ->take(5)
            ->get();

        return view('cash-register.index', compact('openRegister', 'recentRegisters'));
    }

    /**
     * Formulario para abrir caja
     */
    public function open()
    {
        $user = auth()->user();
        $branch = $this->branchContext->current();

        if (!$branch) {
            return redirect()->route('dashboard')
                ->with('error', 'No tienes una sucursal asignada.');
        }

        // Verificar si ya tiene caja abierta
        $existingOpen = CashRegister::where('user_id', $user->id)
            ->where('branch_id', $branch->id)
            ->where('status', 'abierta')
            ->exists();

        if ($existingOpen) {
            return redirect()->route('cash-register.index')
                ->with('error', 'Ya tienes una caja abierta.');
        }

        return view('cash-register.open', [
            'branch' => $branch,
        ]);
    }

    /**
     * Procesar apertura de caja
     */
    public function storeOpen(OpenCashRegisterRequest $request)
    {
        $user = auth()->user();
        $service = new CashRegisterService();

        $branchId = $this->branchContext->currentId();

        try {
            $service->openCashRegister(
                branchId: $branchId,
                userId: $user->id,
                amount: (float) $request->opening_amount,
                notes: $request->opening_notes,
            );

            return redirect()->route('pos.index')
                ->with('success', 'Caja abierta exitosamente. ¡Listo para vender!');

        } catch (\Exception $e) {
            return back()->withInput()
                ->with('error', 'Error al abrir la caja: ' . $e->getMessage());
        }
    }

    /**
     * Formulario para cerrar caja
     */
    public function close()
    {
        $user = auth()->user();

        $openRegister = CashRegister::with(['sales.items', 'branch', 'movements', 'expenses'])
            ->where('user_id', $user->id)
            ->where('branch_id', $this->branchContext->currentId())
            ->where('status', 'abierta')
            ->first();

        if (!$openRegister) {
            return redirect()->route('cash-register.index')
                ->with('error', 'No tienes una caja abierta.');
        }

        // Calcular totales por método de pago
        $salesByMethod = $openRegister->sales->where('status', 'completada')->groupBy('payment_method')->map(function ($sales) {
            return [
                'count' => $sales->count(),
                'total' => $sales->sum('total'),
            ];
        });

        // Obtener movimientos aprobados e información de la caja
        $service = new CashRegisterService();
        $stats = $service->getCashRegisterStats($openRegister);

        return view('cash-register.close', compact('openRegister', 'salesByMethod', 'stats'));
    }

    /**
     * Procesar cierre de caja
     */
    public function storeClose(CloseCashRegisterRequest $request)
    {
        $user = auth()->user();

        $openRegister = CashRegister::where('user_id', $user->id)
            ->where('branch_id', $this->branchContext->currentId())
            ->where('status', 'abierta')
            ->first();

        if (!$openRegister) {
            return redirect()->route('cash-register.index')
                ->with('error', 'No tienes una caja abierta.');
        }

        try {
            $service = new CashRegisterService();
            $closedRegister = $service->closeCashRegister(
                cashRegister: $openRegister,
                closingAmount: (float) $request->closing_amount,
                notes: $request->closing_notes,
            );

            // Calcular la diferencia
            $difference = $closedRegister->difference;

            $message = 'Caja cerrada exitosamente.';
            if ($difference != 0) {
                $diffFormatted = number_format(abs($difference), 2);
                $message .= $difference > 0
                    ? " Sobrante: \${$diffFormatted}"
                    : " Faltante: \${$diffFormatted}";
            }

            return redirect()->route('cash-register.index')
                ->with('success', $message);

        } catch (\Exception $e) {
            return back()->withInput()
                ->with('error', 'Error al cerrar la caja: ' . $e->getMessage());
        }
    }

    /**
     * Detalle de una caja registradora
     */
    public function show(CashRegister $cashRegister)
    {
        $this->authorize('view', $cashRegister);

        $cashRegister->load(['branch', 'user', 'sales.items.product', 'movements.user', 'movements.approver', 'expenses.user']);

        $service = new CashRegisterService();
        $stats = $service->getCashRegisterStats($cashRegister);

        return view('cash-register.show', compact('cashRegister', 'stats'));
    }

    /**
     * Reabrir una caja cerrada (solo Admin)
     */
    public function reopen(CashRegister $cashRegister)
    {
        $this->authorize('reopen', $cashRegister);

        try {
            (new CashRegisterService())->reopenCashRegister($cashRegister);
        } catch (\Exception $e) {
            return back()->with('error', 'No se pudo reabrir la caja: ' . $e->getMessage());
        }

        return redirect()->route('cash-register.show', $cashRegister)
            ->with('success', 'Caja reabierta. Puede recibir ventas y movimientos de nuevo.');
    }

    /**
     * Eliminar una caja de prueba (solo Admin, solo sin ventas)
     */
    public function destroy(CashRegister $cashRegister)
    {
        $this->authorize('delete', $cashRegister);

        try {
            (new CashRegisterService())->deleteCashRegister($cashRegister);
        } catch (\Exception $e) {
            return back()->with('error', 'No se pudo eliminar la caja: ' . $e->getMessage());
        }

        return redirect()->route('cash-registers.history')
            ->with('success', "Caja #{$cashRegister->id} eliminada.");
    }

    /**
     * Registrar un movimiento de caja (AJAX)
     */
    public function addMovement(CashRegisterMovementRequest $request)
    {
        $user = auth()->user();

        // Obtener la caja abierta del usuario
        $cashRegister = CashRegister::where('user_id', $user->id)
            ->where('branch_id', $this->branchContext->currentId())
            ->where('status', 'abierta')
            ->first();

        if (!$cashRegister) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes una caja abierta.',
            ], 400);
        }

        try {
            $service = new CashRegisterService();
            $movement = $service->registerMovement(
                cashRegister: $cashRegister,
                type: $request->type,
                amount: (float) $request->amount,
                reason: $request->reason,
                notes: $request->notes,
                userId: $user->id,
            );

            return response()->json([
                'success' => true,
                'message' => 'Movimiento registrado correctamente. El administrador debe aprobarlo.',
                'movement' => [
                    'id' => $movement->id,
                    'type' => $movement->type,
                    'amount' => number_format($movement->amount, 2),
                    'reason' => $movement->reason,
                    'status' => $movement->status,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar el movimiento: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Aprobar un movimiento de caja (AJAX)
     */
    public function approveMovement(CashRegisterMovement $movement)
    {
        $this->authorize('approve', $movement);

        try {
            $service = new CashRegisterService();
            $approved = $service->approveMovement($movement);

            return response()->json([
                'success' => true,
                'message' => 'Movimiento aprobado correctamente.',
                'movement' => [
                    'id' => $approved->id,
                    'status' => $approved->status,
                    'approved_at' => $approved->approved_at->format('d/m/Y H:i'),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al aprobar el movimiento: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Rechazar un movimiento de caja (AJAX)
     */
    public function rejectMovement(Request $request, CashRegisterMovement $movement)
    {
        $this->authorize('reject', $movement);

        try {
            $service = new CashRegisterService();
            $rejected = $service->rejectMovement($movement);

            return response()->json([
                'success' => true,
                'message' => 'Movimiento rechazado correctamente.',
                'movement' => [
                    'id' => $rejected->id,
                    'status' => $rejected->status,
                    'approved_at' => $rejected->approved_at->format('d/m/Y H:i'),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al rechazar el movimiento: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Historial de cajas (solo Admin)
     */
    public function history(Request $request)
    {
        $this->authorize('viewAny', CashRegister::class);

        $query = CashRegister::with(['user', 'branch'])
            ->withCount('sales')
            ->orderBy('opened_at', 'desc');

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $registers = $query->paginate(20);

        // Cajas abiertas ahora mismo (todas, de cualquier usuario)
        $openRegisters = CashRegister::with(['user', 'branch'])
            ->where('status', 'abierta')
            ->orderBy('opened_at')
            ->get();

        // Obtener sucursales y usuarios para los filtros
        $branches = \App\Models\Branch::where('is_active', true)->orderBy('name')->get();
        $users = \App\Models\User::whereHas('roles')
            ->orderBy('name')
            ->limit(100)
            ->get();

        return view('cash-register.history', compact('registers', 'openRegisters', 'branches', 'users'));
    }
}
