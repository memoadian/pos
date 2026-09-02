<?php

namespace App\Policies;

use App\Models\Expense;
use App\Models\User;

class ExpensePolicy
{
    /**
     * Determine whether the user can view the expenses page.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['Admin', 'Manager']);
    }

    /**
     * Determine whether the user can delete the expense.
     * Admin siempre; el creador solo mientras su caja siga abierta.
     */
    public function delete(User $user, Expense $expense): bool
    {
        if ($user->hasRole('Admin')) {
            return true;
        }

        return $user->id === $expense->user_id
            && $expense->cashRegister?->status === 'abierta';
    }
}
