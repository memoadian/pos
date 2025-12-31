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
            DepartmentSeeder::class,
        ]);

        // Crear usuario Super Admin de prueba
        $adminExists = User::where('email', 'admin@pos.com')->exists();

        if (!$adminExists) {
            $testUser = User::factory()->create([
                'name' => 'Super Admin',
                'email' => 'admin@pos.com',
                'username' => 'admin',
                'is_active' => true,
            ]);

            $testUser->assignRole('Super Admin');
            $this->command->info('✓ Usuario Super Admin creado: admin@pos.com');
        } else {
            $this->command->info('✓ Usuario Super Admin ya existe: admin@pos.com');
        }
    }
}
