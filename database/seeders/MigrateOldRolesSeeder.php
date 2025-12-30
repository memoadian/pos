<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class MigrateOldRolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Mapeo de roles antiguos a nuevos
        $roleMapping = [
            'admin' => 'Admin',
            'gerente' => 'Admin',
            'supervisor' => 'Empresa',
            'cajero' => 'Cliente',
            'almacenista' => 'Cliente',
        ];

        $users = User::all();
        $migratedCount = 0;

        foreach ($users as $user) {
            if (!empty($user->role) && isset($roleMapping[$user->role])) {
                $newRoleName = $roleMapping[$user->role];
                $user->assignRole($newRoleName);
                $migratedCount++;

                $this->command->info("Usuario '{$user->name}': '{$user->role}' → '{$newRoleName}'");
            }
        }

        $this->command->info("✓ {$migratedCount} usuarios migrados al nuevo sistema de roles");
    }
}
