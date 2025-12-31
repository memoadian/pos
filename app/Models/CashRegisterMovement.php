<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashRegisterMovement extends Model
{
    protected $fillable = [
        'cash_register_id',
        'type',
        'amount',
        'reason',
        'notes',
        'user_id',
        'approved_by',
        'status',
        'approved_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'approved_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relación: Pertenece a una caja registradora
     */
    public function cashRegister(): BelongsTo
    {
        return $this->belongsTo(CashRegister::class);
    }

    /**
     * Relación: Usuario que solicita el movimiento
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relación: Admin que aprueba/rechaza el movimiento
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Scope: Movimientos pendientes
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pendiente');
    }

    /**
     * Scope: Movimientos aprobados
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'aprobado');
    }

    /**
     * Scope: Movimientos rechazados
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'rechazado');
    }

    /**
     * Scope: Movimientos de una caja específica
     */
    public function scopeForCashRegister($query, $cashRegisterId)
    {
        return $query->where('cash_register_id', $cashRegisterId);
    }

    /**
     * Verificar si está pendiente
     */
    public function isPending(): bool
    {
        return $this->status === 'pendiente';
    }

    /**
     * Verificar si está aprobado
     */
    public function isApproved(): bool
    {
        return $this->status === 'aprobado';
    }

    /**
     * Verificar si está rechazado
     */
    public function isRejected(): bool
    {
        return $this->status === 'rechazado';
    }

    /**
     * Verificar si es un ingreso
     */
    public function isIncome(): bool
    {
        return $this->type === 'ingreso';
    }

    /**
     * Verificar si es un retiro
     */
    public function isWithdrawal(): bool
    {
        return $this->type === 'retiro';
    }

    /**
     * Calcular el impacto en la caja
     * Los ingresos suman, los retiros restan
     */
    public function getImpactAttribute(): float
    {
        if (!$this->isApproved()) {
            return 0;
        }

        return $this->isIncome()
            ? (float) $this->amount
            : -(float) $this->amount;
    }
}
