<?php

namespace App\Policies;

use App\Models\CashRegisterMovement;
use App\Models\User;

class CashRegisterMovementPolicy
{
    /**
     * Determine if the user can view the movement
     */
    public function view(User $user, CashRegisterMovement $movement): bool
    {
        // El usuario que hizo el movimiento o un admin puede verlo
        return $user->id === $movement->user_id || $user->hasRole(['Admin', 'Admin']);
    }

    /**
     * Determine if the user can approve the movement
     */
    public function approve(User $user, CashRegisterMovement $movement): bool
    {
        // Solo admins pueden aprobar
        return $user->hasRole(['Admin', 'Admin']);
    }

    /**
     * Determine if the user can reject the movement
     */
    public function reject(User $user, CashRegisterMovement $movement): bool
    {
        // Solo admins pueden rechazar
        return $user->hasRole(['Admin', 'Admin']);
    }

    /**
     * Determine if the user can create a movement
     */
    public function create(User $user): bool
    {
        // Cualquier usuario con una caja abierta puede crear movimientos
        return true;
    }
}
