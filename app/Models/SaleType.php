<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SaleType extends Model
{
    protected $fillable = [
        'name',
        'base_unit',
        'allows_decimals',
        'is_active',
    ];

    protected $casts = [
        'allows_decimals' => 'boolean',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the products that use this sale type
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
