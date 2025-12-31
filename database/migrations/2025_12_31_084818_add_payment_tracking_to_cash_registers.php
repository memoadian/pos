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
        Schema::table('cash_registers', function (Blueprint $table) {
            $table->decimal('cash_sales', 10, 2)->default(0)->after('total_profit');
            $table->decimal('card_sales', 10, 2)->default(0)->after('cash_sales');
            $table->decimal('transfer_sales', 10, 2)->default(0)->after('card_sales');
            $table->text('opening_notes')->nullable()->after('transfer_sales');
            $table->text('closing_notes')->nullable()->after('opening_notes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cash_registers', function (Blueprint $table) {
            $table->dropColumn(['cash_sales', 'card_sales', 'transfer_sales', 'opening_notes', 'closing_notes']);
        });
    }
};
