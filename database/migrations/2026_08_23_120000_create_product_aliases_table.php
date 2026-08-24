<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_aliases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            // Como le dice el mostrador al producto: marca, apodo o abreviatura
            // ("dogo", "chupiral"). Varios productos pueden compartir uno.
            $table->string('alias');
            $table->timestamps();

            // Un mismo apodo no se repite dentro del producto.
            $table->unique(['product_id', 'alias']);
            $table->index('alias');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_aliases');
    }
};
