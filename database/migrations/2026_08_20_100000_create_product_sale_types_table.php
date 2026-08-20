<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tipos de venta adicionales de un producto (ej. un producto base en pza que
 * tambien se vende por caja o por kg).
 *
 * Igual que `product_branch_prices`, aqui solo viven los tipos EXTRA: el tipo
 * default sigue siendo `products.sale_type_id`, con sus precios y umbrales en
 * la propia tabla `products` y factor de conversion 1 por definicion (la
 * unidad base del inventario es la del tipo default).
 *
 * `conversion_factor` es cuantas unidades base consume una unidad de este
 * tipo: con base = pza, una caja de 24 lleva factor 24. El costo del tipo no
 * se guarda porque siempre es `conversion_factor * products.cost`, que es lo
 * consistente con lo que se descuenta del inventario.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_sale_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('sale_type_id')->constrained('sale_types');
            $table->decimal('conversion_factor', 12, 4)->default(1);
            $table->decimal('price_retail', 10, 2)->default(0);
            $table->decimal('price_wholesale', 10, 2)->default(0);
            $table->decimal('price_super_wholesale', 10, 2)->default(0);
            $table->unsignedInteger('min_wholesale_qty')->nullable();
            $table->unsignedInteger('min_super_wholesale_qty')->nullable();
            $table->timestamps();

            // Un producto no puede tener el mismo tipo de venta dos veces
            $table->unique(['product_id', 'sale_type_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_sale_types');
    }
};
