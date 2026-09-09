<?php

namespace App\Policies;

use App\Models\SaleType;
use App\Models\User;
use App\Policies\Concerns\AuthorizesWithPermissions;

class SaleTypePolicy
{
    use AuthorizesWithPermissions;

    public function viewAny(User $user): bool
    {
        return $this->check($user, 'ver tipos de venta');
    }

    public function view(User $user, SaleType $saleType): bool
    {
        return $this->check($user, 'ver tipos de venta');
    }

    public function create(User $user): bool
    {
        return $this->check($user, 'crear tipos de venta');
    }

    public function update(User $user, SaleType $saleType): bool
    {
        return $this->check($user, 'editar tipos de venta');
    }

    public function delete(User $user, SaleType $saleType): bool
    {
        return $this->check($user, 'eliminar tipos de venta');
    }
}
