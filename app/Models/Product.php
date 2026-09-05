<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
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
        'auto_wholesale',
        'is_active',
    ];

    protected $attributes = [
        'auto_wholesale' => true,
    ];

    protected $casts = [
        'min_stock' => 'decimal:2',
        'price_retail' => 'decimal:2',
        'price_wholesale' => 'decimal:2',
        'price_super_wholesale' => 'decimal:2',
        'cost' => 'decimal:2',
        'min_wholesale_qty' => 'integer',
        'min_super_wholesale_qty' => 'integer',
        'auto_wholesale' => 'boolean',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Todos los tipos de venta con los que se puede vender este producto,
     * el default primero, ya resueltos para una sucursal.
     *
     * Es la unica fuente de verdad de la feature: el default sale de la propia
     * tabla `products` (factor 1, su unidad es la unidad base del inventario) y
     * los extra de `product_sale_types`. Cada opcion trae sus tres precios ya
     * con el override de sucursal aplicado, sus umbrales efectivos y el factor
     * con el que hay que descontar inventario.
     *
     * @return array<int, array<string, mixed>>
     */
    public function saleTypeOptions(?int $branchId = null): array
    {
        $this->loadMissing(['saleType', 'productSaleTypes.saleType', 'branchPrices']);

        $options = [];

        if ($this->saleType) {
            $options[] = $this->buildSaleTypeOption($this->saleType, null, $branchId);
        }

        foreach ($this->productSaleTypes as $extra) {
            // El tipo principal ya se listo arriba: si quedo tambien como fila
            // extra (por ejemplo tras una importacion que cambio el principal),
            // no se duplica.
            if (! $extra->saleType || (int) $extra->sale_type_id === (int) $this->sale_type_id) {
                continue;
            }

            $options[] = $this->buildSaleTypeOption($extra->saleType, $extra, $branchId);
        }

        return $options;
    }

    /**
     * Armar una opcion de tipo de venta. $extra null = el tipo default, cuyos
     * precios y umbrales viven en las columnas del producto.
     *
     * @return array<string, mixed>
     */
    private function buildSaleTypeOption(SaleType $saleType, ?ProductSaleType $extra, ?int $branchId): array
    {
        $override = $branchId
            ? $this->branchPrices->first(fn ($price) => (int) $price->branch_id === $branchId
                && (int) $price->sale_type_id === (int) $saleType->id)
            : null;

        $source = $extra ?? $this;

        return [
            'sale_type_id' => (int) $saleType->id,
            'name' => $saleType->name,
            'unit' => $saleType->base_unit,
            'allows_decimals' => (bool) $saleType->allows_decimals,
            'is_default' => $extra === null,
            'conversion_factor' => $extra ? (float) $extra->conversion_factor : 1.0,
            'price_retail' => (float) ($override?->price_retail ?? $source->price_retail),
            'price_wholesale' => (float) ($override?->price_wholesale ?? $source->price_wholesale),
            'price_super_wholesale' => (float) ($override?->price_super_wholesale ?? $source->price_super_wholesale),
            // Umbral nulo en el tipo extra = hereda el del producto
            'min_wholesale_qty' => $extra
                ? ($extra->min_wholesale_qty ?? $this->min_wholesale_qty)
                : $this->min_wholesale_qty,
            'min_super_wholesale_qty' => $extra
                ? ($extra->min_super_wholesale_qty ?? $this->min_super_wholesale_qty)
                : $this->min_super_wholesale_qty,
        ];
    }

    /**
     * Resolver un tipo de venta del producto. $saleTypeId null (o el tipo
     * default) regresa el default; un tipo que no pertenece al producto
     * regresa null, para que quien vende pueda rechazarlo.
     *
     * @return array<string, mixed>|null
     */
    public function resolveSaleTypeOption(?int $saleTypeId = null, ?int $branchId = null): ?array
    {
        $options = $this->saleTypeOptions($branchId);

        if ($saleTypeId === null) {
            return $options[0] ?? null;
        }

        foreach ($options as $option) {
            if ($option['sale_type_id'] === $saleTypeId) {
                return $option;
            }
        }

        return null;
    }

    /**
     * Resolve the three price levels for a branch and sale type: the branch
     * override when it exists, falling back to the base price per level.
     *
     * Cada nivel se resuelve por separado, asi una sucursal puede sobrescribir
     * solo el menudeo y seguir heredando mayoreo y super mayoreo. Sin
     * $saleTypeId se responden los precios del tipo default.
     */
    public function effectivePrices(?int $branchId = null, ?int $saleTypeId = null): array
    {
        $option = $this->resolveSaleTypeOption($saleTypeId, $branchId);

        if (! $option) {
            // Producto sin tipo de venta resoluble: los precios base son lo unico que hay
            return [
                'price_retail' => (float) $this->price_retail,
                'price_wholesale' => (float) $this->price_wholesale,
                'price_super_wholesale' => (float) $this->price_super_wholesale,
            ];
        }

        return [
            'price_retail' => $option['price_retail'],
            'price_wholesale' => $option['price_wholesale'],
            'price_super_wholesale' => $option['price_super_wholesale'],
        ];
    }

    /**
     * Get the appropriate price based on quantity, for a given branch and sale type
     */
    public function getPriceForQuantity(float $quantity, ?int $branchId = null, ?int $saleTypeId = null): float
    {
        $prices = $this->effectivePrices($branchId, $saleTypeId);

        // Mayoreo automatico apagado: el producto siempre cobra menudeo, sin
        // importar la cantidad ni los umbrales cargados.
        if ($this->auto_wholesale === false) {
            return $prices['price_retail'];
        }

        $option = $this->resolveSaleTypeOption($saleTypeId, $branchId);

        $wholesaleQty = $option['min_wholesale_qty'] ?? $this->min_wholesale_qty;
        $superWholesaleQty = $option['min_super_wholesale_qty'] ?? $this->min_super_wholesale_qty;

        if ($superWholesaleQty && $quantity >= $superWholesaleQty) {
            return $prices['price_super_wholesale'];
        }

        if ($wholesaleQty && $quantity >= $wholesaleQty) {
            return $prices['price_wholesale'];
        }

        return $prices['price_retail'];
    }

    /**
     * Get the price level name based on quantity
     */
    public function getPriceLevelForQuantity(float $quantity, ?int $saleTypeId = null): string
    {
        if ($this->auto_wholesale === false) {
            return 'retail';
        }

        $option = $this->resolveSaleTypeOption($saleTypeId);

        $wholesaleQty = $option['min_wholesale_qty'] ?? $this->min_wholesale_qty;
        $superWholesaleQty = $option['min_super_wholesale_qty'] ?? $this->min_super_wholesale_qty;

        if ($superWholesaleQty && $quantity >= $superWholesaleQty) {
            return 'super_wholesale';
        }

        if ($wholesaleQty && $quantity >= $wholesaleQty) {
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
     * Get the extra sale types of this product (the default one lives in the
     * `sale_type_id` column)
     */
    public function productSaleTypes(): HasMany
    {
        return $this->hasMany(ProductSaleType::class);
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

    /**
     * Como le dice el mostrador a este producto: marcas, apodos y abreviaturas
     * con las que tambien se le encuentra en el buscador.
     */
    public function aliases(): HasMany
    {
        return $this->hasMany(ProductAlias::class);
    }

    /**
     * Busqueda unica del catalogo: nombre, codigo de barras y alias.
     *
     * El alias entra con whereHas, que compila a un solo EXISTS dentro de la
     * misma consulta: buscar no cuesta una query por producto.
     */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        $term = trim($term);

        return $query->where(function (Builder $q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
                ->orWhere('barcode', 'like', "%{$term}%")
                ->orWhereHas('aliases', fn (Builder $alias) => $alias->where('alias', 'like', "%{$term}%"));
        });
    }

    /**
     * Reemplaza los alias del producto por la lista dada, sin duplicados ni
     * vacios y respetando el unique de (product_id, alias).
     *
     * @param  array<int, string|null>  $aliases
     */
    public function syncAliases(array $aliases): void
    {
        $clean = collect($aliases)
            ->map(fn ($alias) => trim((string) $alias))
            ->filter()
            // El unique de la tabla es sensible a mayusculas segun la collation:
            // se descartan los repetidos comparando en minusculas.
            ->uniqueStrict(fn ($alias) => mb_strtolower($alias))
            ->values();

        $this->aliases()->whereNotIn('alias', $clean)->delete();

        $existing = $this->aliases()->pluck('alias');

        $nuevos = $clean->reject(
            fn ($alias) => $existing->contains(fn ($actual) => mb_strtolower($actual) === mb_strtolower($alias))
        );

        $this->aliases()->createMany($nuevos->map(fn ($alias) => ['alias' => $alias])->all());

        $this->unsetRelation('aliases');
    }
}
