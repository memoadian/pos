<?php

namespace App\Services;

use App\Models\CashRegister;
use App\Models\Expense;
use Exception;
use Illuminate\Support\Facades\DB;

class ExpenseService
{
    /**
     * Registrar un gasto contra una caja. Directo, sin aprobación.
     *
     * @throws Exception
     */
    public function record(
        CashRegister $cashRegister,
        string $category,
        string $description,
        float $amount,
        int $userId
    ): Expense {
        return DB::transaction(function () use ($cashRegister, $category, $description, $amount, $userId) {
            if ($amount <= 0) {
                throw new Exception('El monto debe ser mayor a cero.');
            }

            return Expense::create([
                'cash_register_id' => $cashRegister->id,
                'branch_id' => $cashRegister->branch_id,
                'user_id' => $userId,
                'category' => $category,
                'description' => $description,
                'amount' => $amount,
            ]);
        });
    }

    public function delete(Expense $expense): void
    {
        $expense->delete();
    }
}
