<?php

namespace App\Policies;

use App\Models\Sale;
use App\Models\User;

class SalePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Admin, Manager y Vendedor pueden ver ventas
        return $user->hasRole(['Admin', 'Manager', 'Vendedor']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Sale $sale): bool
    {
        // Puede ver si es suya o es Admin
        return $user->id === $sale->user_id || $user->hasRole(['Admin', 'Admin']);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Admin, Admin y Vendedor pueden crear ventas
        return $user->hasRole(['Admin', 'Admin', 'Vendedor']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Sale $sale): bool
    {
        // Solo Admin puede modificar ventas (para devoluciones futuras)
        return $user->hasRole(['Admin', 'Admin']);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Sale $sale): bool
    {
        // Solo Admin puede eliminar ventas
        return $user->hasRole('Admin');
    }
}
