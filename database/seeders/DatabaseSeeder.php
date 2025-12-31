<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seeders
        $this->call([
            RolePermissionSeeder::class,
            SaleTypeSeeder::class,
            DepartmentSeeder::class,
        ]);

        // Crear usuario Admin de prueba
        $adminExists = User::where('email', 'admin@pos.com')->exists();

        if (!$adminExists) {
            $testUser = User::factory()->create([
                'name' => 'Guillermo',
                'email' => 'admin@pos.com',
                'username' => 'memo',
                'is_active' => true,
            ]);

            $testUser->assignRole('Admin');
            $this->command->info('✓ Usuario Admin creado: admin@pos.com');
        } else {
            $this->command->info('✓ Usuario Admin ya existe: admin@pos.com');
        }
    }
}
