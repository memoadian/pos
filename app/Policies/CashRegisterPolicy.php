<?php

namespace App\Policies;

use App\Models\CashRegister;
use App\Models\User;
use App\Services\BranchContextService;

class CashRegisterPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Solo Admin y Admin pueden ver todas las cajas
        return $user->hasRole(['Admin', 'Admin']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, CashRegister $cashRegister): bool
    {
        // Puede ver si es suya o es Admin
        return $user->id === $cashRegister->user_id || $user->hasRole(['Admin', 'Admin']);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Cualquier usuario con una sucursal resuelta (fija o de contexto) puede abrir caja
        return app(BranchContextService::class)->currentId($user) !== null;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, CashRegister $cashRegister): bool
    {
        // Solo puede cerrar su propia caja o Admin
        return $user->id === $cashRegister->user_id || $user->hasRole(['Admin', 'Admin']);
    }

    /**
     * Determine whether the user can delete the model.
     * Solo Admin, y solo si la caja no tiene ventas (borrarla arrastraría en
     * cascada ventas/partidas y dejaría el inventario descuadrado).
     */
    public function delete(User $user, CashRegister $cashRegister): bool
    {
        return $user->hasRole('Admin') && !$cashRegister->sales()->exists();
    }

    /**
     * Determine whether the user can reopen a closed cash register.
     */
    public function reopen(User $user, CashRegister $cashRegister): bool
    {
        return $user->hasRole('Admin') && $cashRegister->status === 'cerrada';
    }
}
