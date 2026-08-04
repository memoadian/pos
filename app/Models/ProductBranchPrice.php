<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductBranchPrice extends Model
{
    protected $fillable = [
        'product_id',
        'branch_id',
        'price_retail',
        'price_wholesale',
        'price_super_wholesale',
    ];

    protected $casts = [
        'price_retail' => 'decimal:2',
        'price_wholesale' => 'decimal:2',
        'price_super_wholesale' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Los tres niveles de precio que pueden sobrescribirse por sucursal
     */
    public const PRICE_FIELDS = [
        'price_retail',
        'price_wholesale',
        'price_super_wholesale',
    ];

    /**
     * Whether this override still carries any value. Si queda vacio, la fila
     * debe borrarse para que el producto vuelva a heredar sus precios base.
     */
    public function isEmpty(): bool
    {
        foreach (self::PRICE_FIELDS as $field) {
            if ($this->{$field} !== null) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get the product that owns this price override
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the branch this price override applies to
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
