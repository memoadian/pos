<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashRegister extends Model
{
    protected $fillable = [
        'branch_id',
        'user_id',
        'opened_at',
        'closed_at',
        'opening_amount',
        'closing_amount',
        'total_sales',
        'total_profit',
        'cash_sales',
        'card_sales',
        'transfer_sales',
        'opening_notes',
        'closing_notes',
        'status',
    ];

    protected $casts = [
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
        'opening_amount' => 'decimal:2',
        'closing_amount' => 'decimal:2',
        'total_sales' => 'decimal:2',
        'total_profit' => 'decimal:2',
        'cash_sales' => 'decimal:2',
        'card_sales' => 'decimal:2',
        'transfer_sales' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the branch that owns the cash register
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the user who operates this cash register
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the sales for this cash register
     */
    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    /**
     * Get the movements for this cash register
     */
    public function movements(): HasMany
    {
        return $this->hasMany(CashRegisterMovement::class);
    }

    /**
     * Get the expenses charged against this cash register
     */
    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    /**
     * Total de gastos registrados en esta caja
     */
    public function getTotalExpensesAttribute(): float
    {
        return (float) $this->expenses()->sum('amount');
    }

    /**
     * Get approved movements
     */
    public function getApprovedMovementsAttribute()
    {
        return $this->movements()->approved()->get();
    }

    /**
     * Get pending movements
     */
    public function getPendingMovementsAttribute()
    {
        return $this->movements()->pending()->get();
    }

    /**
     * Get total approved income movements
     */
    public function getTotalApprovedIncomesAttribute(): float
    {
        return (float) $this->movements()
            ->approved()
            ->where('type', 'ingreso')
            ->sum('amount');
    }

    /**
     * Get total approved withdrawal movements
     */
    public function getTotalApprovedWithdrawalsAttribute(): float
    {
        return (float) $this->movements()
            ->approved()
            ->where('type', 'retiro')
            ->sum('amount');
    }

    /**
     * Calculate the expected amount in the cash register
     * Formula: opening_amount + cash_sales + approved_incomes - approved_withdrawals - expenses
     */
    public function getExpectedAmountAttribute(): float
    {
        return (float) $this->opening_amount
            + (float) $this->cash_sales
            + $this->total_approved_incomes
            - $this->total_approved_withdrawals
            - $this->total_expenses;
    }

    /**
     * Calculate the difference between expected and closing amount
     */
    public function getDifferenceAttribute(): float
    {
        if (!$this->closing_amount) {
            return 0;
        }

        return (float) $this->closing_amount - $this->expected_amount;
    }

    /**
     * Check if there are pending movements
     */
    public function hasPendingMovements(): bool
    {
        return $this->movements()->pending()->exists();
    }
}
