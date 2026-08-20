<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Snapshot del tipo de venta usado en cada partida.
 *
 * Se guarda tambien el factor con el que se cobro para que el ticket y los
 * reportes historicos no dependan de como este configurado el producto hoy.
 * Nullable/1 para las ventas anteriores a la feature: todas se cobraron en la
 * unidad base.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->foreignId('sale_type_id')->nullable()->after('product_id')->constrained('sale_types');
            $table->decimal('conversion_factor', 12, 4)->default(1)->after('quantity');
        });
    }

    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropForeign(['sale_type_id']);
            $table->dropColumn(['sale_type_id', 'conversion_factor']);
        });
    }
};
