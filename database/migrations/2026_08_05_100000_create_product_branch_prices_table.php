<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Precios diferenciados por sucursal.
 *
 * A diferencia de `inventories`, aqui NO existe una fila obligatoria por cada
 * combinacion producto-sucursal: solo se guarda una fila cuando una sucursal
 * necesita un precio distinto al del producto. Si no hay fila (o la columna
 * viene nula), se hereda el precio base de `products`.
 *
 * Esto mantiene la feature a prueba de sucursales dinamicas: dar de alta una
 * sucursal nueva no requiere backfill de precios, todo hereda el base.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_branch_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->decimal('price_retail', 10, 2)->nullable();
            $table->decimal('price_wholesale', 10, 2)->nullable();
            $table->decimal('price_super_wholesale', 10, 2)->nullable();
            $table->timestamps();

            // Un solo override por producto por sucursal
            $table->unique(['product_id', 'branch_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_branch_prices');
    }
};
