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

        // ==========================================
        // CREAR PERMISOS - GRUPO: INVENTARIO
        // ==========================================
        $permissionsInventario = [
            ['name' => 'ver inventario', 'guard_name' => 'web', 'group' => 'Inventario', 'description' => 'Permite ver el inventario de productos'],
            ['name' => 'registrar movimientos inventario', 'guard_name' => 'web', 'group' => 'Inventario', 'description' => 'Permite registrar entradas y salidas de inventario'],
            ['name' => 'ajustar stock', 'guard_name' => 'web', 'group' => 'Inventario', 'description' => 'Permite ajustar el stock directamente'],
        ];

        $allPermissions = array_merge($permissionsEnvios, $permissionsPagos, $permissionsInventario);

        foreach ($allPermissions as $permissionData) {
            Permission::create($permissionData);
        }

        // ==========================================
        // CREAR ROLES Y ASIGNAR PERMISOS
        // ==========================================

        // ROL: EMPLEADO
        $roleEmpleado = Role::create([
            'name' => 'Empleado',
            'guard_name' => 'web',
            'description' => 'Empleado con acceso solo lectura al inventario',
        ]);
        $roleEmpleado->givePermissionTo([
            'ver inventario',
        ]);

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
        $this->command->info('  - 14 permisos creados (6 Envios + 5 Pagos + 3 Inventario)');
        $this->command->info('  - 5 roles creados: Empleado, Cliente, Empresa, Admin, Super Admin');
    }
}
