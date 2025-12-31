<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Cambiar sale_type de string a foreignId
            $table->dropColumn('sale_type');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('sale_type_id')->after('name')->constrained('sale_types');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['sale_type_id']);
            $table->dropColumn('sale_type_id');
            $table->string('sale_type')->default('pieza');
        });
    }
};
