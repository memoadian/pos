<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_types', function (Blueprint $table) {
            $table->dropUnique(['code']);
            $table->dropColumn('code');
            $table->unique('name');
        });
    }

    public function down(): void
    {
        Schema::table('sale_types', function (Blueprint $table) {
            $table->dropUnique(['name']);
            $table->string('code')->unique()->after('name');
        });
    }
};
