<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleItem extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'sale_id',
        'product_id',
        'sale_type_id',
        'quantity',
        'conversion_factor',
        'unit_price',
        'cost',
        'total',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'conversion_factor' => 'decimal:4',
        'unit_price' => 'decimal:2',
        'cost' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    /**
     * Get the sale that owns this item
     */
    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    /**
     * Get the product for this item
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the sale type this item was sold with (null en ventas anteriores a
     * los tipos multiples: todas se cobraron en la unidad base)
     */
    public function saleType(): BelongsTo
    {
        return $this->belongsTo(SaleType::class);
    }
}
