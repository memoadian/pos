<?php

namespace App\Policies;

use App\Models\User;
use App\Policies\Concerns\AuthorizesWithPermissions;

class UserPolicy
{
    use AuthorizesWithPermissions;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $this->check($user, 'ver usuarios');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, User $model): bool
    {
        return $this->check($user, 'ver usuarios');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $this->check($user, 'crear usuarios');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, User $model): bool
    {
        return $this->check($user, 'editar usuarios');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, User $model): bool
    {
        // Cannot delete yourself
        if ($user->id === $model->id) {
            return false;
        }

        return $this->check($user, 'eliminar usuarios');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, User $model): bool
    {
        return $this->check($user, 'eliminar usuarios');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, User $model): bool
    {
        // El borrado permanente queda reservado al rol Admin.
        return $user->hasRole('Admin');
    }
}
