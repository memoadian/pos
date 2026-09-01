<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Sale extends Model
{
    protected $fillable = [
        'branch_id',
        'cash_register_id',
        'user_id',
        'client_id',
        'subtotal',
        'total',
        'profit',
        'payment_method',
        'idempotency_key',
        'status',
        'cancelled_at',
        'cancelled_by',
        'cancellation_reason',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'total' => 'decimal:2',
        'profit' => 'decimal:2',
        'cancelled_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Ventas vigentes (no canceladas): lo que debe contar en reportes,
     * dashboard y totales.
     */
    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', 'completada');
    }

    /**
     * Ventas canceladas.
     */
    public function scopeCancelled(Builder $query): Builder
    {
        return $query->where('status', 'cancelada');
    }

    /**
     * ¿La venta está cancelada?
     */
    public function isCancelled(): bool
    {
        return $this->status === 'cancelada';
    }

    /**
     * Get the branch for this sale
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the cash register for this sale
     */
    public function cashRegister(): BelongsTo
    {
        return $this->belongsTo(CashRegister::class);
    }

    /**
     * Get the user who made this sale
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the client for this sale
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get the user who cancelled this sale
     */
    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    /**
     * Get the items for this sale
     */
    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    /**
     * Get the invoice for this sale
     */
    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }
}
