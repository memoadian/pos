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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->constrained('departments')->cascadeOnDelete();
            $table->string('barcode')->unique();
            $table->string('name');
            $table->string('sale_type')->default('pieza'); // pieza, granel, peso
            $table->string('unit_base')->default('pza'); // kg, lt, pza, etc
            $table->decimal('price_retail', 10, 2)->default(0);
            $table->decimal('price_wholesale', 10, 2)->default(0);
            $table->decimal('price_super_wholesale', 10, 2)->default(0);
            $table->decimal('cost', 10, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
