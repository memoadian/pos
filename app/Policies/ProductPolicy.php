<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;
use App\Policies\Concerns\AuthorizesWithPermissions;

class ProductPolicy
{
    use AuthorizesWithPermissions;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $this->check($user, 'ver productos');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Product $product): bool
    {
        return $this->check($user, 'ver productos');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $this->check($user, 'crear productos');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Product $product): bool
    {
        return $this->check($user, 'editar productos');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Product $product): bool
    {
        return $this->check($user, 'eliminar productos');
    }

    /**
     * Determine whether the user can import products in bulk.
     */
    public function import(User $user): bool
    {
        return $this->check($user, 'importar productos');
    }

    /**
     * Determine whether the user can manage per-branch prices.
     */
    public function manageBranchPrices(User $user): bool
    {
        return $this->check($user, 'gestionar precios sucursal');
    }
}
