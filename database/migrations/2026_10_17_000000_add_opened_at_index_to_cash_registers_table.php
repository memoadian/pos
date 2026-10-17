<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Los reportes por caja diaria filtran y agrupan por opened_at.
     */
    public function up(): void
    {
        Schema::table('cash_registers', function (Blueprint $table) {
            $table->index('opened_at');
        });
    }

    public function down(): void
    {
        Schema::table('cash_registers', function (Blueprint $table) {
            $table->dropIndex(['opened_at']);
        });
    }
};
