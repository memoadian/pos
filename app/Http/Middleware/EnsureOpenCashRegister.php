<?php

namespace App\Http\Middleware;

use App\Models\CashRegister;
use App\Services\BranchContextService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOpenCashRegister
{
    public function __construct(private BranchContextService $branchContext)
    {
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();
        $branch = $this->branchContext->current();
        if (!$branch) {
            return redirect()->route('dashboard')
                ->with('error', 'No tienes una sucursal asignada. Contacta al administrador.');
        }
        if (!$branch->is_active) {
            return redirect()->route('dashboard')
                ->with('error', 'Tu sucursal está inactiva.');
        }

        $openRegister = CashRegister::where('user_id', $user->id)
            ->where('branch_id', $branch->id)
            ->where('status', 'abierta')
            ->first();

        if (!$openRegister) {
            return redirect()->route('cash-register.index')
                ->with('error', 'Debes abrir una caja antes de realizar ventas.');
        }

        $request->merge(['current_cash_register' => $openRegister]);

        return $next($request);
    }
}
