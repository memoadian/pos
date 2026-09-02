<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    /**
     * Categorías de gasto. La clave se guarda en la columna `category`; el valor
     * es la etiqueta que se muestra. "otro" cubre lo que no encaje en la lista
     * (el detalle va en la descripción).
     */
    public const CATEGORIES = [
        'proveedor' => 'Proveedor / Mercancía',
        'servicios' => 'Servicios (luz, agua, internet)',
        'renta' => 'Renta',
        'sueldos' => 'Sueldos',
        'limpieza' => 'Limpieza',
        'transporte' => 'Transporte',
        'mantenimiento' => 'Mantenimiento',
        'otro' => 'Otro',
    ];

    protected $fillable = [
        'cash_register_id',
        'branch_id',
        'user_id',
        'category',
        'description',
        'amount',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Etiqueta legible de la categoría.
     */
    public function categoryLabel(): string
    {
        return self::CATEGORIES[$this->category] ?? $this->category;
    }

    public function cashRegister(): BelongsTo
    {
        return $this->belongsTo(CashRegister::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
