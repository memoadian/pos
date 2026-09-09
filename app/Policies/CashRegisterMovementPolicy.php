<?php

namespace App\Policies;

use App\Models\CashRegisterMovement;
use App\Models\User;
use App\Policies\Concerns\AuthorizesWithPermissions;

class CashRegisterMovementPolicy
{
    use AuthorizesWithPermissions;

    /**
     * Determine if the user can view the movement
     */
    public function view(User $user, CashRegisterMovement $movement): bool
    {
        // El usuario que hizo el movimiento o quien puede aprobarlos
        return $user->id === $movement->user_id || $this->check($user, 'aprobar movimientos caja');
    }

    /**
     * Determine if the user can approve the movement
     */
    public function approve(User $user, CashRegisterMovement $movement): bool
    {
        return $this->check($user, 'aprobar movimientos caja');
    }

    /**
     * Determine if the user can reject the movement
     */
    public function reject(User $user, CashRegisterMovement $movement): bool
    {
        return $this->check($user, 'aprobar movimientos caja');
    }

    /**
     * Determine if the user can create a movement
     */
    public function create(User $user): bool
    {
        // Cualquier usuario con una caja abierta puede solicitar movimientos
        return true;
    }
}
