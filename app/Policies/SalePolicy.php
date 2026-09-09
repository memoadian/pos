<?php

namespace App\Policies;

use App\Models\Sale;
use App\Models\User;
use App\Policies\Concerns\AuthorizesWithPermissions;

class SalePolicy
{
    use AuthorizesWithPermissions;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $this->check($user, 'ver ventas');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Sale $sale): bool
    {
        return $user->id === $sale->user_id || $this->check($user, 'ver ventas');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $this->check($user, 'usar punto de venta');
    }

    /**
     * Determine whether the user can update the model (devoluciones futuras).
     */
    public function update(User $user, Sale $sale): bool
    {
        return $this->check($user, 'cancelar ventas');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Sale $sale): bool
    {
        return $user->hasRole('Admin');
    }

    /**
     * Determine whether the user can cancel the sale.
     * El caso "ya cancelada" lo maneja SaleService con un error amistoso,
     * no un 403.
     */
    public function cancel(User $user, Sale $sale): bool
    {
        return $this->check($user, 'cancelar ventas');
    }
}
