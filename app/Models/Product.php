<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'department_id',
        'barcode',
        'name',
        'sale_type_id',
        'unit_base',
        'min_stock',
        'price_retail',
        'price_wholesale',
        'price_super_wholesale',
        'cost',
        'min_wholesale_qty',
        'min_super_wholesale_qty',
        'is_active',
    ];

    protected $casts = [
        'min_stock' => 'decimal:2',
        'price_retail' => 'decimal:2',
        'price_wholesale' => 'decimal:2',
        'price_super_wholesale' => 'decimal:2',
        'cost' => 'decimal:2',
        'min_wholesale_qty' => 'integer',
        'min_super_wholesale_qty' => 'integer',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the appropriate price based on quantity
     */
    public function getPriceForQuantity(float $quantity): float
    {
        if ($this->min_super_wholesale_qty && $quantity >= $this->min_super_wholesale_qty) {
            return (float) $this->price_super_wholesale;
        }

        if ($this->min_wholesale_qty && $quantity >= $this->min_wholesale_qty) {
            return (float) $this->price_wholesale;
        }

        return (float) $this->price_retail;
    }

    /**
     * Get the price level name based on quantity
     */
    public function getPriceLevelForQuantity(float $quantity): string
    {
        if ($this->min_super_wholesale_qty && $quantity >= $this->min_super_wholesale_qty) {
            return 'super_wholesale';
        }

        if ($this->min_wholesale_qty && $quantity >= $this->min_wholesale_qty) {
            return 'wholesale';
        }

        return 'retail';
    }

    /**
     * Get the department that owns the product
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get the sale type that owns the product
     */
    public function saleType(): BelongsTo
    {
        return $this->belongsTo(SaleType::class);
    }

    /**
     * Get the inventories for this product
     */
    public function inventories(): HasMany
    {
        return $this->hasMany(Inventory::class);
    }

    /**
     * Get the inventory movements for this product
     */
    public function inventoryMovements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    /**
     * Get the sale items for this product
     */
    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }
}
