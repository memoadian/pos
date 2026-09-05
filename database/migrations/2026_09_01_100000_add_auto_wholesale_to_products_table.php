<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Interruptor por producto para el precio de mayoreo automatico del POS.
 *
 * Con `auto_wholesale` en false el POS ignora los umbrales de mayoreo y super
 * mayoreo de ese producto: siempre cobra menudeo y el ajuste manual de precio
 * puede subir hasta el menudeo. Sirve para productos con precio de mayoreo
 * cargado que en realidad se venden por pieza (una promocion, no un mayoreo).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('auto_wholesale')->default(true)->after('min_super_wholesale_qty');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('auto_wholesale');
        });
    }
};
