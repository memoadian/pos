<?php

namespace App\Policies;

use App\Models\User;
use App\Policies\Concerns\AuthorizesWithPermissions;
use Spatie\Permission\Models\Role;

class RolePolicy
{
    use AuthorizesWithPermissions;

    public function viewAny(User $user): bool
    {
        return $this->check($user, 'ver roles');
    }

    public function view(User $user, Role $role): bool
    {
        return $this->check($user, 'ver roles');
    }

    public function create(User $user): bool
    {
        return $this->check($user, 'crear roles');
    }

    public function update(User $user, Role $role): bool
    {
        return $this->check($user, 'editar roles');
    }

    public function delete(User $user, Role $role): bool
    {
        // Los roles del sistema no se pueden borrar (lo revalida el controlador).
        if (in_array($role->name, ['Admin', 'Manager', 'Vendedor'], true)) {
            return false;
        }

        return $this->check($user, 'eliminar roles');
    }
}
