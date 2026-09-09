<?php

namespace App\Policies;

use App\Models\User;
use App\Policies\Concerns\AuthorizesWithPermissions;
use Spatie\Permission\Models\Permission;

class PermissionPolicy
{
    use AuthorizesWithPermissions;

    public function viewAny(User $user): bool
    {
        return $this->check($user, 'ver permisos');
    }

    public function view(User $user, Permission $permission): bool
    {
        return $this->check($user, 'ver permisos');
    }

    public function create(User $user): bool
    {
        return $this->check($user, 'crear permisos');
    }

    public function update(User $user, Permission $permission): bool
    {
        return $this->check($user, 'editar permisos');
    }

    public function delete(User $user, Permission $permission): bool
    {
        return $this->check($user, 'eliminar permisos');
    }
}
