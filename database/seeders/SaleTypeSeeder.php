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
            ['name' => 'Pieza', 'base_unit' => 'pza'],
            ['name' => 'Granel', 'base_unit' => 'kg'],
            ['name' => 'Peso', 'base_unit' => 'kg'],
            ['name' => 'Mililitros', 'base_unit' => 'ml'],
            ['name' => 'Litros', 'base_unit' => 'lt'],
            ['name' => 'Kilogramos', 'base_unit' => 'kg'],
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
