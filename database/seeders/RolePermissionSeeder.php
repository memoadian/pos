<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // ==========================================
        // CREAR PERMISOS - GRUPO: SISTEMA
        // ==========================================
        $permissionsSystem = [
            ['name' => 'crear usuarios', 'guard_name' => 'web', 'group' => 'Sistema', 'description' => 'Permite crear nuevos usuarios'],
        ];

        $allPermissions = array_merge($permissionsSystem);

        foreach ($allPermissions as $permissionData) {
            Permission::create($permissionData);
        }

        // ==========================================
        // CREAR ROLES Y ASIGNAR PERMISOS
        // ==========================================

        // ROL: VENDEDOR
        $roleVendedor = Role::create([
            'name' => 'Vendedor',
            'guard_name' => 'web',
            'description' => 'Vendedor con acceso a POS y caja',
        ]);
        $roleVendedor->givePermissionTo([]);

        // ROL: MANAGER (acceso a sucursales asignadas: inventario y movimientos)
        $roleManager = Role::firstOrCreate(
            ['name' => 'Manager', 'guard_name' => 'web'],
            ['description' => 'Gerente con acceso a sucursales asignadas (inventario, movimientos)']
        );
        $roleManager->givePermissionTo([]);

        // ROL: ADMIN (11 permisos - todos)
        $roleAdmin = Role::create([
            'name' => 'Admin',
            'guard_name' => 'web',
            'description' => 'Administrador con acceso completo al sistema',
        ]);
        $roleAdmin->givePermissionTo(Permission::all());

        $this->command->info('✓ Roles y permisos creados exitosamente');
        $this->command->info('  - 14 permisos creados (6 Envios + 5 Pagos + 3 Inventario)');
        $this->command->info('  - 5 roles creados: Empleado, Cliente, Empresa, Admin, Admin');
    }
}
