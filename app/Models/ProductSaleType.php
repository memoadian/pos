<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Un tipo de venta EXTRA de un producto (el default vive en `products`).
 *
 * Ver la migracion `create_product_sale_types_table` para el porque de este
 * modelo: precios propios por tipo y factor de conversion hacia la unidad base.
 */
class ProductSaleType extends Model
{
    protected $fillable = [
        'product_id',
        'sale_type_id',
        'conversion_factor',
        'price_retail',
        'price_wholesale',
        'price_super_wholesale',
        'min_wholesale_qty',
        'min_super_wholesale_qty',
    ];

    protected $casts = [
        'conversion_factor' => 'decimal:4',
        'price_retail' => 'decimal:2',
        'price_wholesale' => 'decimal:2',
        'price_super_wholesale' => 'decimal:2',
        'min_wholesale_qty' => 'integer',
        'min_super_wholesale_qty' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the product that owns this sale type
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the sale type this row configures
     */
    public function saleType(): BelongsTo
    {
        return $this->belongsTo(SaleType::class);
    }
}
