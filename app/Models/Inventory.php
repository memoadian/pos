<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Inventory extends Model
{
    protected $fillable = [
        'product_id',
        'branch_id',
        'stock_quantity',
    ];

    protected $casts = [
        'stock_quantity' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Boot the model and add event listeners
     */
    protected static function boot()
    {
        parent::boot();

        // Validar que el stock nunca sea negativo antes de guardar
        static::saving(function ($inventory) {
            if ($inventory->stock_quantity < 0) {
                throw new \Exception('El stock no puede ser negativo');
            }
        });
    }

    /**
     * Get the product that owns the inventory
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the branch that owns the inventory
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
