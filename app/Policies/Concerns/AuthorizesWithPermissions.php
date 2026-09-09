<?php

namespace App\Policies\Concerns;

use App\Models\User;

trait AuthorizesWithPermissions
{
    /**
     * El rol "Admin" siempre pasa ("acceso completo al sistema"); cualquier
     * otro rol necesita tener asignado el permiso de Spatie correspondiente
     * (Sistema → Roles → Editar).
     *
     * Se usa `can()` (vía Gate) y no `hasPermissionTo()` para que un permiso
     * todavía no registrado devuelva `false` en lugar de lanzar excepción.
     *
     * Para quitar el atajo por rol en el futuro basta con eliminar el
     * `hasRole('Admin')` de aquí y garantizar que el rol Admin tenga todos
     * los permisos (lo hace RolePermissionSeeder).
     */
    protected function check(User $user, string $permission): bool
    {
        return $user->hasRole('Admin') || $user->can($permission);
    }
}
