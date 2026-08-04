<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_types', function (Blueprint $table) {
            $table->string('base_unit', 50)->default('pza')->after('code');
        });
    }

    public function down(): void
    {
        Schema::table('sale_types', function (Blueprint $table) {
            $table->dropColumn('base_unit');
        });
    }
};
