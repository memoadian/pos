<?php

namespace App\Http\Middleware;

use App\Models\CashRegister;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOpenCashRegister
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        // Admins pueden acceder sin restricciones
        if ($user->hasRole(['Admin', 'Admin'])) {
            return $next($request);
        }

        // Para Vendedores: verificar que el usuario tenga sucursal asignada
        if (!$user->branch_id) {
            return redirect()->route('dashboard')
                ->with('error', 'No tienes una sucursal asignada. Contacta al administrador.');
        }

        // Verificar que la sucursal esté activa
        if (!$user->branch || !$user->branch->is_active) {
            return redirect()->route('dashboard')
                ->with('error', 'Tu sucursal está inactiva.');
        }

        // Buscar caja abierta para el usuario
        $openRegister = CashRegister::where('user_id', $user->id)
            ->where('branch_id', $user->branch_id)
            ->where('status', 'abierta')
            ->first();

        if (!$openRegister) {
            return redirect()->route('cash-register.index')
                ->with('error', 'Debes abrir una caja antes de realizar ventas.');
        }

        // Compartir la caja abierta con la request
        $request->merge(['current_cash_register' => $openRegister]);

        return $next($request);
    }
}
