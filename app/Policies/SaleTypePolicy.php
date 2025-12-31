<?php

namespace App\Policies;

use App\Models\SaleType;
use App\Models\User;

class SaleTypePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, SaleType $saleType): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasRole(['Admin', 'Admin']);
    }

    public function update(User $user, SaleType $saleType): bool
    {
        return $user->hasRole(['Admin', 'Admin']);
    }

    public function delete(User $user, SaleType $saleType): bool
    {
        return $user->hasRole(['Admin', 'Admin']);
    }
}
