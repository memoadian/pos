<div class="flex flex-col h-full">
    <!-- Logo -->
    <div class="h-14 flex items-center gap-3 px-4 border-b border-slate-200 flex-shrink-0">
        <x-brand-mark box-class="w-9 h-9 rounded-lg" />
        <div>
            <p class="font-semibold text-slate-900 text-sm">{{ setting('site_name') }}</p>
            <p class="text-xs text-slate-500">v1.0</p>
        </div>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 overflow-y-auto p-3 space-y-1">
        <a class="flex items-center gap-3 px-3 py-2 text-sm text-slate-600 rounded-lg hover:bg-slate-50 transition-colors"
            data-nav href="{{ route('dashboard') }}">
            <i class="bi bi-grid-1x2 text-lg"></i>
            <span>Dashboard</span>
        </a>

        @can('usar punto de venta')
        <a class="flex items-center gap-3 px-3 py-2 text-sm text-slate-600 rounded-lg hover:bg-slate-50 transition-colors"
            data-nav href="{{ route('pos.index') }}">
            <i class="bi bi-cart3 text-lg"></i>
            <span>Punto de Venta</span>
        </a>
        @endcan

        @can('viewAny', App\Models\Product::class)
        <a class="flex items-center gap-3 px-3 py-2 text-sm text-slate-600 rounded-lg hover:bg-slate-50 transition-colors"
            data-nav href="{{ route('products.index') }}">
            <i class="bi bi-box-seam text-lg"></i>
            <span>Productos</span>
        </a>
        @endcan

        @can('viewAny', App\Models\Inventory::class)
        <a class="flex items-center gap-3 px-3 py-2 text-sm text-slate-600 rounded-lg hover:bg-slate-50 transition-colors"
            data-nav href="{{ route('inventory.index') }}">
            <i class="bi bi-boxes text-lg"></i>
            <span>Inventario</span>
        </a>
        @endcan

        @can('viewMovements', App\Models\Inventory::class)
        <a class="flex items-center gap-3 px-3 py-2 text-sm text-slate-600 rounded-lg hover:bg-slate-50 transition-colors"
            data-nav href="{{ route('inventory-movements.index') }}">
            <i class="bi bi-arrow-left-right text-lg"></i>
            <span>Movimientos</span>
        </a>
        @endcan

        @can('ver caja')
            <a class="flex items-center gap-3 px-3 py-2 text-sm text-slate-600 rounded-lg hover:bg-slate-50 transition-colors"
                data-nav href="{{ route('cash-register.index') }}">
                <i class="bi bi-cash-stack text-lg"></i>
                <span>Mi Caja</span>
            </a>
        @endcan

        @canany(['ver ventas', 'ver historial cajas', 'ver gastos', 'ver reportes'])
            <div class="pt-4">
                <p class="px-3 text-xs font-medium text-slate-400 uppercase tracking-wider mb-2">Gestión</p>

                @can('viewAny', App\Models\Sale::class)
                    <a class="flex items-center gap-3 px-3 py-2 text-sm text-slate-600 rounded-lg hover:bg-slate-50 transition-colors"
                        data-nav href="{{ route('sales.index') }}">
                        <i class="bi bi-receipt text-lg"></i>
                        <span>Ventas</span>
                    </a>
                @endcan

                @can('viewAny', App\Models\CashRegister::class)
                    <a class="flex items-center gap-3 px-3 py-2 text-sm text-slate-600 rounded-lg hover:bg-slate-50 transition-colors"
                        data-nav href="{{ route('cash-registers.history') }}">
                        <i class="bi bi-clock-history text-lg"></i>
                        <span>Cajas</span>
                    </a>
                @endcan

                @can('viewAny', App\Models\Expense::class)
                    <a class="flex items-center gap-3 px-3 py-2 text-sm text-slate-600 rounded-lg hover:bg-slate-50 transition-colors"
                        data-nav href="{{ route('expenses.index') }}">
                        <i class="bi bi-cash-stack text-lg"></i>
                        <span>Gastos</span>
                    </a>
                @endcan

                @can('ver reportes')
                    <a class="flex items-center gap-3 px-3 py-2 text-sm text-slate-600 rounded-lg hover:bg-slate-50 transition-colors"
                        data-nav href="{{ route('reports.index') }}">
                        <i class="bi bi-graph-up text-lg"></i>
                        <span>Reportes</span>
                    </a>
                @endcan
            </div>
        @endcanany

        @canany(['ver sucursales', 'ver departamentos', 'ver tipos de venta'])
            <div class="pt-4">
                <p class="px-3 text-xs font-medium text-slate-400 uppercase tracking-wider mb-2">Admin</p>

                @can('viewAny', App\Models\Branch::class)
                <a class="flex items-center gap-3 px-3 py-2 text-sm text-slate-600 rounded-lg hover:bg-slate-50 transition-colors"
                    data-nav href="{{ route('branches.index') }}">
                    <i class="bi bi-building text-lg"></i>
                    <span>Sucursales</span>
                </a>
                @endcan

                @can('viewAny', App\Models\Department::class)
                <a class="flex items-center gap-3 px-3 py-2 text-sm text-slate-600 rounded-lg hover:bg-slate-50 transition-colors"
                    data-nav href="{{ route('departments.index') }}">
                    <i class="bi bi-folder text-lg"></i>
                    <span>Departamentos</span>
                </a>
                @endcan

                @can('viewAny', App\Models\SaleType::class)
                <a class="flex items-center gap-3 px-3 py-2 text-sm text-slate-600 rounded-lg hover:bg-slate-50 transition-colors"
                    data-nav href="{{ route('sale-types.index') }}">
                    <i class="bi bi-tag text-lg"></i>
                    <span>Tipos de Venta</span>
                </a>
                @endcan
            </div>
        @endcanany

        @canany(['ver usuarios', 'ver roles', 'ver permisos', 'ver configuracion'])
            <div class="pt-4">
                <p class="px-3 text-xs font-medium text-slate-400 uppercase tracking-wider mb-2">Sistema</p>

                @can('viewAny', App\Models\User::class)
                <a class="flex items-center gap-3 px-3 py-2 text-sm text-slate-600 rounded-lg hover:bg-slate-50 transition-colors"
                    data-nav href="{{ route('users.index') }}">
                    <i class="bi bi-people text-lg"></i>
                    <span>Usuarios</span>
                </a>
                @endcan

                @can('viewAny', Spatie\Permission\Models\Role::class)
                <a class="flex items-center gap-3 px-3 py-2 text-sm text-slate-600 rounded-lg hover:bg-slate-50 transition-colors"
                    data-nav href="{{ route('roles.index') }}">
                    <i class="bi bi-shield-lock text-lg"></i>
                    <span>Roles</span>
                </a>
                @endcan

                @can('viewAny', Spatie\Permission\Models\Permission::class)
                <a class="flex items-center gap-3 px-3 py-2 text-sm text-slate-600 rounded-lg hover:bg-slate-50 transition-colors"
                    data-nav href="{{ route('permissions.index') }}">
                    <i class="bi bi-key text-lg"></i>
                    <span>Permisos</span>
                </a>
                @endcan

                @can('ver configuracion')
                <a class="flex items-center gap-3 px-3 py-2 text-sm text-slate-600 rounded-lg hover:bg-slate-50 transition-colors"
                    data-nav href="{{ route('settings.edit') }}">
                    <i class="bi bi-gear text-lg"></i>
                    <span>Configuración</span>
                </a>
                @endcan
            </div>
        @endcanany
    </nav>

    <!-- Footer -->
    <div class="p-3 border-t border-slate-200 flex-shrink-0">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button
                class="w-full flex items-center gap-3 px-3 py-2 text-sm text-slate-600 rounded-lg hover:bg-red-50 hover:text-red-600 transition-colors"
                type="submit">
                <i class="bi bi-box-arrow-left text-lg"></i>
                <span>Cerrar Sesión</span>
            </button>
        </form>
    </div>
</div>
