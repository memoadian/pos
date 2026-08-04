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
            ['name' => 'Pieza', 'code' => 'pieza', 'base_unit' => 'pza'],
            ['name' => 'Granel', 'code' => 'granel', 'base_unit' => 'kg'],
            ['name' => 'Peso', 'code' => 'peso', 'base_unit' => 'kg'],
            ['name' => 'Mililitros', 'code' => 'ml', 'base_unit' => 'ml'],
            ['name' => 'Litros', 'code' => 'lt', 'base_unit' => 'lt'],
            ['name' => 'Kilogramos', 'code' => 'kg', 'base_unit' => 'kg'],
        ];

        foreach ($saleTypes as $saleType) {
            SaleType::updateOrCreate(
                ['code' => $saleType['code']],
                $saleType
            );

            $this->command->info("✓ Tipo de venta creado/actualizado: {$saleType['name']}");
        }
    }
}
