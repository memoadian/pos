<?php

namespace App\Policies;

use App\Models\Branch;
use App\Models\User;

class BranchPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Todos pueden ver sucursales
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Branch $branch): bool
    {
        // Todos pueden ver una sucursal
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Solo Admin y Admin pueden crear
        return $user->hasRole(['Admin', 'Admin']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Branch $branch): bool
    {
        // Solo Admin y Admin pueden actualizar
        return $user->hasRole(['Admin', 'Admin']);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Branch $branch): bool
    {
        // Solo Admin y Admin pueden eliminar
        return $user->hasRole(['Admin', 'Admin']);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Branch $branch): bool
    {
        // Solo Admin y Admin pueden restaurar
        return $user->hasRole(['Admin', 'Admin']);
    }
}
