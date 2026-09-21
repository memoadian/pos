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
        Schema::table('sales', function (Blueprint $table) {
            // Hora real del cobro segun el reloj del cajero; created_at sigue
            // siendo la hora en que el servidor recibio la venta (que para
            // una venta offline puede ser bastante despues).
            $table->timestamp('sold_at')->nullable()->after('idempotency_key');

            // Folio provisional (OFF-XXXXXXXX) impreso en el ticket cuando la
            // venta se cobro sin conexion, para poder relacionar ese ticket
            // con el registro ya sincronizado.
            $table->string('offline_ref', 20)->nullable()->unique()->after('sold_at');

            // Una venta offline se acepta aunque el servidor ya no tenga
            // stock suficiente (la venta ya ocurrio fisicamente): se marca
            // aqui para que un admin la revise, en vez de rechazarla.
            $table->boolean('stock_issue')->default(false)->after('offline_ref');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['sold_at', 'offline_ref', 'stock_issue']);
        });
    }
};
