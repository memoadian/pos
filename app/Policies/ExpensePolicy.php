<?php

namespace App\Policies;

use App\Models\Expense;
use App\Models\User;
use App\Policies\Concerns\AuthorizesWithPermissions;

class ExpensePolicy
{
    use AuthorizesWithPermissions;

    /**
     * Determine whether the user can view the expenses page.
     */
    public function viewAny(User $user): bool
    {
        return $this->check($user, 'ver gastos');
    }

    /**
     * Determine whether the user can register an expense against the open register.
     */
    public function create(User $user): bool
    {
        return $this->check($user, 'registrar gastos');
    }

    /**
     * Determine whether the user can delete the expense.
     * Quien tenga el permiso siempre; el creador solo mientras su caja siga abierta.
     */
    public function delete(User $user, Expense $expense): bool
    {
        if ($this->check($user, 'eliminar gastos')) {
            return true;
        }

        return $user->id === $expense->user_id
            && $expense->cashRegister?->status === 'abierta';
    }
}
