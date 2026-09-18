<?php

namespace Database\Seeders;

use App\Models\SaleType;
use Illuminate\Database\Seeder;

class SaleTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $saleTypes = [
            ['name' => 'Pieza', 'base_unit' => 'pza', 'allows_decimals' => false],
            // A granel/peso/volumen se vende en fracciones (1.5 kg, 0.750 L):
            // sin esto el POS solo deja cantidades enteras para estos tipos.
            ['name' => 'Granel', 'base_unit' => 'kg', 'allows_decimals' => true],
            ['name' => 'Peso', 'base_unit' => 'kg', 'allows_decimals' => true],
            ['name' => 'Mililitros', 'base_unit' => 'ml', 'allows_decimals' => true],
            ['name' => 'Litros', 'base_unit' => 'lt', 'allows_decimals' => true],
            ['name' => 'Kilogramos', 'base_unit' => 'kg', 'allows_decimals' => true],
        ];

        foreach ($saleTypes as $saleType) {
            SaleType::updateOrCreate(
                ['name' => $saleType['name']],
                $saleType
            );

            $this->command->info("✓ Tipo de venta creado/actualizado: {$saleType['name']}");
        }
    }
}
