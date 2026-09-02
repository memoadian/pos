<?php

namespace App\Services;

use App\Models\CashRegister;
use App\Models\CashRegisterMovement;
use Exception;
use Illuminate\Support\Facades\DB;

class CashRegisterService
{
    /**
     * Abrir una caja registradora
     *
     * @param int $branchId ID de la sucursal
     * @param int $userId ID del usuario
     * @param float $amount Monto inicial
     * @param string|null $notes Notas opcionales
     * @return CashRegister
     * @throws Exception
     */
    public function openCashRegister(
        int $branchId,
        int $userId,
        float $amount,
        ?string $notes = null
    ): CashRegister {
        return DB::transaction(function () use ($branchId, $userId, $amount, $notes) {
            // Verificar si ya existe una caja abierta
            $existingOpen = CashRegister::where('user_id', $userId)
                ->where('branch_id', $branchId)
                ->where('status', 'abierta')
                ->exists();

            if ($existingOpen) {
                throw new Exception('Ya tienes una caja abierta en esa sucursal.');
            }

            return CashRegister::create([
                'branch_id' => $branchId,
                'user_id' => $userId,
                'opened_at' => now(),
                'opening_amount' => $amount,
                'total_sales' => 0,
                'total_profit' => 0,
                'cash_sales' => 0,
                'card_sales' => 0,
                'transfer_sales' => 0,
                'opening_notes' => $notes,
                'status' => 'abierta',
            ]);
        });
    }

    /**
     * Cerrar una caja registradora
     *
     * @param CashRegister $cashRegister
     * @param float $closingAmount Monto final contado
     * @param string|null $notes Notas opcionales
     * @return CashRegister
     * @throws Exception
     */
    public function closeCashRegister(
        CashRegister $cashRegister,
        float $closingAmount,
        ?string $notes = null
    ): CashRegister {
        return DB::transaction(function () use ($cashRegister, $closingAmount, $notes) {
            // Verificar que no haya movimientos pendientes
            if ($cashRegister->hasPendingMovements()) {
                throw new Exception(
                    'No puedes cerrar la caja mientras hay movimientos pendientes de aprobación. ' .
                    'El administrador debe aprobar o rechazar todos los movimientos primero.'
                );
            }

            $cashRegister->update([
                'closed_at' => now(),
                'closing_amount' => $closingAmount,
                'closing_notes' => $notes,
                'status' => 'cerrada',
            ]);

            return $cashRegister->fresh();
        });
    }

    /**
     * Registrar un movimiento de caja (ingreso o retiro)
     *
     * @param CashRegister $cashRegister
     * @param string $type 'ingreso' o 'retiro'
     * @param float $amount Monto
     * @param string $reason Razón/categoría
     * @param string|null $notes Notas adicionales
     * @param int $userId ID del usuario que solicita
     * @return CashRegisterMovement
     * @throws Exception
     */
    public function registerMovement(
        CashRegister $cashRegister,
        string $type,
        float $amount,
        string $reason,
        ?string $notes = null,
        ?int $userId = null
    ): CashRegisterMovement {
        return DB::transaction(function () use ($cashRegister, $type, $amount, $reason, $notes, $userId) {
            if ($type !== 'ingreso' && $type !== 'retiro') {
                throw new Exception("El tipo de movimiento debe ser 'ingreso' o 'retiro'.");
            }

            if ($amount <= 0) {
                throw new Exception('El monto debe ser mayor a cero.');
            }

            $userId = $userId ?? auth()->id();

            return CashRegisterMovement::create([
                'cash_register_id' => $cashRegister->id,
                'type' => $type,
                'amount' => $amount,
                'reason' => $reason,
                'notes' => $notes,
                'user_id' => $userId,
                'status' => 'pendiente',
            ]);
        });
    }

    /**
     * Aprobar un movimiento de caja
     *
     * @param CashRegisterMovement $movement
     * @param int|null $adminId ID del admin que aprueba (por defecto el usuario actual)
     * @return CashRegisterMovement
     * @throws Exception
     */
    public function approveMovement(
        CashRegisterMovement $movement,
        ?int $adminId = null
    ): CashRegisterMovement {
        return DB::transaction(function () use ($movement, $adminId) {
            if (!$movement->isPending()) {
                throw new Exception('Solo puedes aprobar movimientos pendientes.');
            }

            $adminId = $adminId ?? auth()->id();

            $movement->update([
                'status' => 'aprobado',
                'approved_by' => $adminId,
                'approved_at' => now(),
            ]);

            return $movement->fresh();
        });
    }

    /**
     * Rechazar un movimiento de caja
     *
     * @param CashRegisterMovement $movement
     * @param int|null $adminId ID del admin que rechaza
     * @return CashRegisterMovement
     * @throws Exception
     */
    public function rejectMovement(
        CashRegisterMovement $movement,
        ?int $adminId = null
    ): CashRegisterMovement {
        return DB::transaction(function () use ($movement, $adminId) {
            if (!$movement->isPending()) {
                throw new Exception('Solo puedes rechazar movimientos pendientes.');
            }

            $adminId = $adminId ?? auth()->id();

            $movement->update([
                'status' => 'rechazado',
                'approved_by' => $adminId,
                'approved_at' => now(),
            ]);

            return $movement->fresh();
        });
    }

    /**
     * Obtener estadísticas de una caja
     *
     * @param CashRegister $cashRegister
     * @return array
     */
    public function getCashRegisterStats(CashRegister $cashRegister): array
    {
        return [
            'opening_amount' => (float) $cashRegister->opening_amount,
            'cash_sales' => (float) $cashRegister->cash_sales,
            'card_sales' => (float) $cashRegister->card_sales,
            'transfer_sales' => (float) $cashRegister->transfer_sales,
            'total_sales' => (float) $cashRegister->total_sales,
            'total_profit' => (float) $cashRegister->total_profit,
            'total_expenses' => (float) $cashRegister->total_expenses,
            'total_approved_incomes' => (float) $cashRegister->total_approved_incomes,
            'total_approved_withdrawals' => (float) $cashRegister->total_approved_withdrawals,
            'expected_amount' => (float) $cashRegister->expected_amount,
            'closing_amount' => (float) ($cashRegister->closing_amount ?? 0),
            'difference' => (float) ($cashRegister->difference ?? 0),
            'pending_movements_count' => $cashRegister->movements()->pending()->count(),
            'approved_movements_count' => $cashRegister->movements()->approved()->count(),
        ];
    }
}
