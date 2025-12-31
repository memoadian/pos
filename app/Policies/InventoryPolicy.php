<?php

namespace App\Policies;

use App\Models\Inventory;
use App\Models\User;

class InventoryPolicy
{
    /**
     * Determine if the user can view any inventory records.
     */
    public function viewAny(User $user): bool
    {
        // Todos los usuarios autenticados pueden ver el inventario
        return true;
    }

    /**
     * Determine if the user can view a specific inventory record.
     */
    public function view(User $user, Inventory $inventory): bool
    {
        // Todos los usuarios autenticados pueden ver un inventario específico
        return true;
    }

    /**
     * Determine if the user can create inventory movements.
     */
    public function createMovement(User $user): bool
    {
        // Solo Admin y Super Admin pueden crear movimientos
        return $user->hasRole(['Admin', 'Super Admin']);
    }

    /**
     * Determine if the user can adjust stock.
     */
    public function adjustStock(User $user): bool
    {
        // Solo Admin y Super Admin pueden ajustar stock
        return $user->hasRole(['Admin', 'Super Admin']);
    }
}
