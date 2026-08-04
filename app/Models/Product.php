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
     * Resolve the three price levels for a branch: the branch override when it
     * exists, falling back to this product's base price per level.
     *
     * Cada nivel se resuelve por separado, asi una sucursal puede sobrescribir
     * solo el menudeo y seguir heredando mayoreo y super mayoreo.
     */
    public function effectivePrices(?int $branchId = null): array
    {
        $override = $branchId
            ? $this->branchPrices->firstWhere('branch_id', $branchId)
            : null;

        return [
            'price_retail' => (float) ($override?->price_retail ?? $this->price_retail),
            'price_wholesale' => (float) ($override?->price_wholesale ?? $this->price_wholesale),
            'price_super_wholesale' => (float) ($override?->price_super_wholesale ?? $this->price_super_wholesale),
        ];
    }

    /**
     * Get the appropriate price based on quantity, for a given branch
     */
    public function getPriceForQuantity(float $quantity, ?int $branchId = null): float
    {
        $prices = $this->effectivePrices($branchId);

        if ($this->min_super_wholesale_qty && $quantity >= $this->min_super_wholesale_qty) {
            return $prices['price_super_wholesale'];
        }

        if ($this->min_wholesale_qty && $quantity >= $this->min_wholesale_qty) {
            return $prices['price_wholesale'];
        }

        return $prices['price_retail'];
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
     * Get the per-branch price overrides for this product.
     * Solo existen filas para las sucursales con precio distinto al base.
     */
    public function branchPrices(): HasMany
    {
        return $this->hasMany(ProductBranchPrice::class);
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
