<?php

namespace App\Policies;

use App\Models\Inventory;
use App\Models\User;
use App\Policies\Concerns\AuthorizesWithPermissions;

class InventoryPolicy
{
    use AuthorizesWithPermissions;

    /**
     * Determine if the user can view any inventory records.
     */
    public function viewAny(User $user): bool
    {
        return $this->check($user, 'ver inventario');
    }

    /**
     * Determine if the user can view a specific inventory record.
     */
    public function view(User $user, Inventory $inventory): bool
    {
        return $this->check($user, 'ver inventario');
    }

    /**
     * Determine if the user can view the stock movements listing.
     */
    public function viewMovements(User $user): bool
    {
        return $this->check($user, 'ver movimientos inventario');
    }

    /**
     * Determine if the user can create inventory movements.
     */
    public function createMovement(User $user): bool
    {
        return $this->check($user, 'registrar movimientos inventario');
    }

    /**
     * Determine if the user can adjust stock.
     */
    public function adjustStock(User $user): bool
    {
        return $this->check($user, 'registrar movimientos inventario');
    }
}
