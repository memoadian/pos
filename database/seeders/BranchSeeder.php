<?php

namespace Database\Seeders;

use App\Models\Branch;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $branches = [
            ['name' => 'Sucursal Centro', 'address' => 'Av. Juárez 123, Centro'],
            ['name' => 'Sucursal Norte', 'address' => 'Blvd. Independencia 456, Col. Norte'],
            ['name' => 'Sucursal Sur', 'address' => 'Calle Hidalgo 789, Col. Sur'],
        ];

        foreach ($branches as $branch) {
            Branch::firstOrCreate(
                ['name' => $branch['name']],
                $branch + ['is_active' => true]
            );

            $this->command->info("✓ Sucursal creada/actualizada: {$branch['name']}");
        }
    }
}
