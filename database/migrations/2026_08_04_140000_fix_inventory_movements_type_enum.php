<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * La migración original de esta tabla se editó en el mismo archivo después de
 * ya haber corrido en producción (enum('entrada','salida') -> enum('IN','OUT','ADJUST')).
 * Laravel no vuelve a ejecutar migraciones ya registradas, así que en cualquier
 * entorno donde ya corrió la versión vieja, la columna sigue con los valores
 * legados y cualquier insert con 'IN'/'OUT'/'ADJUST' truena por truncamiento.
 * Esta migración normaliza la columna al enum actual en cualquier entorno,
 * sin importar en qué versión se haya quedado.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Se amplía el enum temporalmente para poder mapear los valores legados
        // sin truncar filas existentes (si las hay).
        DB::statement("ALTER TABLE inventory_movements MODIFY type ENUM('entrada','salida','IN','OUT','ADJUST') NOT NULL");

        DB::table('inventory_movements')->where('type', 'entrada')->update(['type' => 'IN']);
        DB::table('inventory_movements')->where('type', 'salida')->update(['type' => 'OUT']);

        DB::statement("ALTER TABLE inventory_movements MODIFY type ENUM('IN','OUT','ADJUST') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE inventory_movements MODIFY type ENUM('entrada','salida','IN','OUT','ADJUST') NOT NULL");

        DB::table('inventory_movements')->where('type', 'IN')->update(['type' => 'entrada']);
        DB::table('inventory_movements')->where('type', 'OUT')->update(['type' => 'salida']);

        DB::statement("ALTER TABLE inventory_movements MODIFY type ENUM('entrada','salida') NOT NULL");
    }
};
