<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /**
     * Catálogo de permisos del sistema, agrupado por módulo.
     *
     * Se usa `updateOrCreate` sobre el nombre, de modo que el seeder es
     * idempotente: puede volver a ejecutarse en producción para añadir
     * permisos nuevos sin tocar los que ya existen ni las asignaciones
     * manuales que se hayan hecho desde la interfaz.
     *
     * @var array<string, array<string, string>> grupo => [nombre => descripción]
     */
    private array $catalog = [
        'Dashboard' => [
            'ver dashboard' => 'Ver el panel principal con los indicadores del negocio',
        ],
        'Punto de Venta' => [
            'usar punto de venta' => 'Acceder al punto de venta y registrar ventas',
            'aplicar descuentos' => 'Aplicar descuentos manuales sobre la venta',
        ],
        'Ventas' => [
            'ver ventas' => 'Ver el listado de todas las ventas',
            'ver detalle ventas' => 'Ver el detalle y el ticket de una venta',
            'cancelar ventas' => 'Cancelar o anular una venta ya registrada',
        ],
        'Caja' => [
            'ver caja' => 'Ver el estado de la caja propia',
            'abrir caja' => 'Abrir una caja para iniciar el turno',
            'cerrar caja' => 'Cerrar una caja y hacer el corte',
            'registrar movimientos caja' => 'Registrar entradas y salidas de efectivo en la caja',
            'aprobar movimientos caja' => 'Aprobar o rechazar los movimientos de caja pendientes',
            'ver historial cajas' => 'Ver el historial de cajas de todas las sucursales',
            'reabrir cajas' => 'Reabrir una caja que ya fue cerrada',
            'eliminar cajas' => 'Eliminar una caja sin ventas asociadas',
        ],
        'Gastos' => [
            'ver gastos' => 'Ver el listado de gastos',
            'registrar gastos' => 'Registrar gastos contra la caja abierta',
            'eliminar gastos' => 'Eliminar un gasto (corrección)',
        ],
        'Productos' => [
            'ver productos' => 'Ver el catálogo de productos',
            'crear productos' => 'Crear nuevos productos',
            'editar productos' => 'Editar la información de los productos',
            'eliminar productos' => 'Eliminar productos',
            'importar productos' => 'Importar productos de forma masiva desde archivo',
            'gestionar precios sucursal' => 'Configurar precios de producto por sucursal',
        ],
        'Inventario' => [
            'ver inventario' => 'Ver las existencias de inventario',
        ],
        'Movimientos' => [
            'ver movimientos inventario' => 'Ver los movimientos de inventario',
            'registrar movimientos inventario' => 'Registrar entradas, salidas y ajustes de inventario',
        ],
        'Reportes' => [
            'ver reportes' => 'Ver los reportes de ventas y operación',
        ],
        'Sucursales' => [
            'ver sucursales' => 'Ver el listado de sucursales',
            'crear sucursales' => 'Crear nuevas sucursales',
            'editar sucursales' => 'Editar la información de las sucursales',
            'eliminar sucursales' => 'Eliminar o restaurar sucursales',
            'cambiar sucursal activa' => 'Cambiar la sucursal de trabajo (contexto)',
        ],
        'Departamentos' => [
            'ver departamentos' => 'Ver el listado de departamentos',
            'crear departamentos' => 'Crear nuevos departamentos',
            'editar departamentos' => 'Editar la información de los departamentos',
            'eliminar departamentos' => 'Eliminar departamentos',
        ],
        'Tipos de Venta' => [
            'ver tipos de venta' => 'Ver el listado de tipos de venta',
            'crear tipos de venta' => 'Crear nuevos tipos de venta',
            'editar tipos de venta' => 'Editar los tipos de venta',
            'eliminar tipos de venta' => 'Eliminar tipos de venta',
        ],
        'Usuarios' => [
            'ver usuarios' => 'Ver el listado de usuarios',
            'crear usuarios' => 'Crear nuevos usuarios',
            'editar usuarios' => 'Editar la información y roles de los usuarios',
            'eliminar usuarios' => 'Eliminar usuarios',
            'impersonar usuarios' => 'Iniciar sesión como otro usuario',
        ],
        'Roles' => [
            'ver roles' => 'Ver el listado de roles',
            'crear roles' => 'Crear nuevos roles',
            'editar roles' => 'Editar un rol y sus permisos',
            'eliminar roles' => 'Eliminar roles',
        ],
        'Permisos' => [
            'ver permisos' => 'Ver el listado de permisos',
            'crear permisos' => 'Crear nuevos permisos',
            'editar permisos' => 'Editar un permiso',
            'eliminar permisos' => 'Eliminar permisos',
        ],
        'Configuración' => [
            'ver configuracion' => 'Ver la configuración del sitio',
            'editar configuracion' => 'Editar la configuración del sitio (nombre, color, logo, ticket)',
        ],
    ];

    /**
     * Permisos por defecto para roles no administradores.
     * Solo se aplican cuando el rol todavía no tiene ningún permiso,
     * para no pisar las asignaciones hechas manualmente.
     *
     * @var array<string, list<string>>
     */
    private array $defaults = [
        'Vendedor' => [
            'ver dashboard',
            'usar punto de venta',
            'ver caja', 'abrir caja', 'cerrar caja', 'registrar movimientos caja',
            'ver gastos', 'registrar gastos',
            'ver productos',
            'ver inventario',
            'ver movimientos inventario',
        ],
        'Manager' => [
            'ver dashboard',
            'usar punto de venta', 'aplicar descuentos',
            'ver ventas', 'ver detalle ventas',
            'ver caja', 'abrir caja', 'cerrar caja', 'registrar movimientos caja', 'ver historial cajas',
            'ver gastos', 'registrar gastos', 'eliminar gastos',
            'ver productos', 'gestionar precios sucursal',
            'ver inventario',
            'ver movimientos inventario', 'registrar movimientos inventario',
            'ver reportes',
            'cambiar sucursal activa',
        ],
    ];

    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Catálogo de permisos (idempotente).
        foreach ($this->catalog as $group => $permissions) {
            foreach ($permissions as $name => $description) {
                Permission::updateOrCreate(
                    ['name' => $name, 'guard_name' => 'web'],
                    ['group' => $group, 'description' => $description],
                );
            }
        }

        // 2. Roles base.
        Role::firstOrCreate(
            ['name' => 'Vendedor', 'guard_name' => 'web'],
            ['description' => 'Vendedor con acceso a POS y caja']
        );
        Role::firstOrCreate(
            ['name' => 'Manager', 'guard_name' => 'web'],
            ['description' => 'Gerente con acceso a sucursales asignadas (inventario, movimientos, reportes)']
        );
        $admin = Role::firstOrCreate(
            ['name' => 'Admin', 'guard_name' => 'web'],
            ['description' => 'Administrador con acceso completo al sistema']
        );

        // 3. Admin siempre tiene todos los permisos.
        $admin->givePermissionTo(Permission::all());

        // 4. Roles no admin: solo se siembra un set por defecto si están vacíos.
        foreach ($this->defaults as $roleName => $permissionNames) {
            $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();

            if ($role && $role->permissions()->count() === 0) {
                $role->givePermissionTo($permissionNames);
            }
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $total = Permission::count();
        $this->command->info("✓ Catálogo de permisos sincronizado ({$total} permisos)");
        $this->command->info('✓ Roles: Vendedor, Manager, Admin');
    }
}
