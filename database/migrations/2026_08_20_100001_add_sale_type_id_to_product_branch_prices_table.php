<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Los precios por sucursal ahora se sobrescriben por (producto, sucursal, tipo
 * de venta): un producto que se vende por pza y por caja puede necesitar un
 * precio distinto de la caja en una sucursal sin tocar el de la pza.
 *
 * Las filas existentes son overrides del tipo default del producto, asi que se
 * rellenan con `products.sale_type_id`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_branch_prices', function (Blueprint $table) {
            $table->unsignedBigInteger('sale_type_id')->nullable()->after('branch_id');
        });

        // Backfill fila por fila (la tabla solo tiene overrides, es chica) para
        // no depender de la sintaxis de UPDATE con JOIN de cada motor.
        DB::table('product_branch_prices')->orderBy('id')->chunkById(200, function ($rows) {
            foreach ($rows as $row) {
                DB::table('product_branch_prices')
                    ->where('id', $row->id)
                    ->update([
                        'sale_type_id' => DB::table('products')->where('id', $row->product_id)->value('sale_type_id'),
                    ]);
            }
        });

        Schema::table('product_branch_prices', function (Blueprint $table) {
            $table->unsignedBigInteger('sale_type_id')->nullable(false)->change();
            $table->foreign('sale_type_id')->references('id')->on('sale_types');

            // El indice nuevo se crea ANTES de borrar el viejo: comparte el
            // prefijo `product_id`, asi MySQL siempre tiene un indice con el
            // que respaldar la llave foranea del producto.
            $table->unique(['product_id', 'branch_id', 'sale_type_id']);
        });

        Schema::table('product_branch_prices', function (Blueprint $table) {
            $table->dropUnique('product_branch_prices_product_id_branch_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('product_branch_prices', function (Blueprint $table) {
            $table->unique(['product_id', 'branch_id']);
        });

        Schema::table('product_branch_prices', function (Blueprint $table) {
            $table->dropUnique('product_branch_prices_product_id_branch_id_sale_type_id_unique');
            $table->dropForeign(['sale_type_id']);
            $table->dropColumn('sale_type_id');
        });
    }
};
