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
            ['name' => 'Pieza', 'code' => 'pieza'],
            ['name' => 'Granel', 'code' => 'granel'],
            ['name' => 'Peso', 'code' => 'peso'],
            ['name' => 'Mililitros', 'code' => 'ml'],
            ['name' => 'Litros', 'code' => 'lt'],
            ['name' => 'Kilogramos', 'code' => 'kg'],
        ];

        foreach ($saleTypes as $saleType) {
            SaleType::firstOrCreate(
                ['code' => $saleType['code']],
                $saleType
            );

            $this->command->info("✓ Tipo de venta creado/actualizado: {$saleType['name']}");
        }
    }
}
