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
        // CREAR PERMISOS - GRUPO: ENVIOS
        // ==========================================
        $permissionsEnvios = [
            ['name' => 'crear envios', 'guard_name' => 'web', 'group' => 'Envios', 'description' => 'Permite crear nuevos envíos'],
            ['name' => 'ver envios propios', 'guard_name' => 'web', 'group' => 'Envios', 'description' => 'Permite ver solo los envíos propios'],
            ['name' => 'ver todos los envios', 'guard_name' => 'web', 'group' => 'Envios', 'description' => 'Permite ver todos los envíos del sistema'],
            ['name' => 'editar envios', 'guard_name' => 'web', 'group' => 'Envios', 'description' => 'Permite editar información de envíos'],
            ['name' => 'cancelar envios', 'guard_name' => 'web', 'group' => 'Envios', 'description' => 'Permite cancelar envíos'],
            ['name' => 'carga masiva', 'guard_name' => 'web', 'group' => 'Envios', 'description' => 'Permite realizar carga masiva de envíos'],
        ];

        // ==========================================
        // CREAR PERMISOS - GRUPO: PAGOS
        // ==========================================
        $permissionsPagos = [
            ['name' => 'ver saldo', 'guard_name' => 'web', 'group' => 'Pagos', 'description' => 'Permite ver el saldo disponible'],
            ['name' => 'recargar saldo', 'guard_name' => 'web', 'group' => 'Pagos', 'description' => 'Permite recargar el saldo disponible'],
            ['name' => 'modificar saldo usuarios', 'guard_name' => 'web', 'group' => 'Pagos', 'description' => 'Permite modificar saldo de otros usuarios'],
            ['name' => 'pedir credito', 'guard_name' => 'web', 'group' => 'Pagos', 'description' => 'Permite solicitar crédito'],
            ['name' => 'gestionar metodos pago', 'guard_name' => 'web', 'group' => 'Pagos', 'description' => 'Permite gestionar métodos de pago'],
        ];

        $allPermissions = array_merge($permissionsEnvios, $permissionsPagos);

        foreach ($allPermissions as $permissionData) {
            Permission::create($permissionData);
        }

        // ==========================================
        // CREAR ROLES Y ASIGNAR PERMISOS
        // ==========================================

        // ROL: CLIENTE (5 permisos)
        $roleCliente = Role::create([
            'name' => 'Cliente',
            'guard_name' => 'web',
            'description' => 'Usuario cliente con funcionalidades básicas de envíos y pagos',
        ]);
        $roleCliente->givePermissionTo([
            'crear envios',
            'ver envios propios',
            'ver saldo',
            'recargar saldo',
            'pedir credito',
        ]);

        // ROL: EMPRESA (10 permisos)
        $roleEmpresa = Role::create([
            'name' => 'Empresa',
            'guard_name' => 'web',
            'description' => 'Usuario tipo empresa con funcionalidades avanzadas de envíos y pagos',
        ]);
        $roleEmpresa->givePermissionTo([
            'crear envios',
            'ver envios propios',
            'ver todos los envios',
            'editar envios',
            'carga masiva',
            'ver saldo',
            'recargar saldo',
            'pedir credito',
            'gestionar metodos pago',
            'modificar saldo usuarios',
        ]);

        // ROL: ADMIN (11 permisos - todos)
        $roleAdmin = Role::create([
            'name' => 'Admin',
            'guard_name' => 'web',
            'description' => 'Administrador con acceso completo al sistema',
        ]);
        $roleAdmin->givePermissionTo(Permission::all());

        // ROL: SUPER ADMIN (11 permisos - todos + privilegios especiales)
        $roleSuperAdmin = Role::create([
            'name' => 'Super Admin',
            'guard_name' => 'web',
            'description' => 'Super Administrador con control total del sistema',
        ]);
        $roleSuperAdmin->givePermissionTo(Permission::all());

        $this->command->info('✓ Roles y permisos creados exitosamente');
        $this->command->info('  - 11 permisos creados (6 Envios + 5 Pagos)');
        $this->command->info('  - 4 roles creados: Cliente, Empresa, Admin, Super Admin');
    }
}
