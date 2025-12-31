<div class="flex flex-col h-full">
    <!-- Logo -->
    <div class="h-14 flex items-center gap-3 px-4 border-b border-slate-200 flex-shrink-0">
        <div class="w-9 h-9 bg-cyan-600 rounded-lg flex items-center justify-center">
            <i class="bi bi-shop text-white"></i>
        </div>
        <div>
            <p class="font-semibold text-slate-900 text-sm">POS Limpieza</p>
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

        <a class="flex items-center gap-3 px-3 py-2 text-sm text-slate-600 rounded-lg hover:bg-slate-50 transition-colors"
            data-nav href="#">
            <i class="bi bi-cart3 text-lg"></i>
            <span>Ventas</span>
        </a>

        <a class="flex items-center gap-3 px-3 py-2 text-sm text-slate-600 rounded-lg hover:bg-slate-50 transition-colors"
            data-nav href="#">
            <i class="bi bi-box-seam text-lg"></i>
            <span>Productos</span>
        </a>

        <a class="flex items-center gap-3 px-3 py-2 text-sm text-slate-600 rounded-lg hover:bg-slate-50 transition-colors"
            data-nav href="#">
            <i class="bi bi-boxes text-lg"></i>
            <span>Inventario</span>
        </a>

        @if (auth()->user()->role === 'cajero')
            <a class="flex items-center gap-3 px-3 py-2 text-sm text-slate-600 rounded-lg hover:bg-slate-50 transition-colors"
                data-nav href="#">
                <i class="bi bi-cash-stack text-lg"></i>
                <span>Mi Caja</span>
            </a>
        @endif

        @if (in_array(auth()->user()->role, ['supervisor', 'gerente', 'admin']))
            <div class="pt-4">
                <p class="px-3 text-xs font-medium text-slate-400 uppercase tracking-wider mb-2">Gestión</p>

                <a class="flex items-center gap-3 px-3 py-2 text-sm text-slate-600 rounded-lg hover:bg-slate-50 transition-colors"
                    data-nav href="#">
                    <i class="bi bi-graph-up text-lg"></i>
                    <span>Reportes</span>
                </a>
            </div>
        @endif

        @if (auth()->user()->hasRole(['Admin', 'Super Admin']))
            <div class="pt-4">
                <p class="px-3 text-xs font-medium text-slate-400 uppercase tracking-wider mb-2">Admin</p>

                <a class="flex items-center gap-3 px-3 py-2 text-sm text-slate-600 rounded-lg hover:bg-slate-50 transition-colors"
                    data-nav href="{{ route('branches.index') }}">
                    <i class="bi bi-building text-lg"></i>
                    <span>Sucursales</span>
                </a>

                <a class="flex items-center gap-3 px-3 py-2 text-sm text-slate-600 rounded-lg hover:bg-slate-50 transition-colors"
                    data-nav href="{{ route('departments.index') }}">
                    <i class="bi bi-folder text-lg"></i>
                    <span>Departamentos</span>
                </a>
            </div>
            <div class="pt-4">
                <p class="px-3 text-xs font-medium text-slate-400 uppercase tracking-wider mb-2">Sistema</p>

                <a class="flex items-center gap-3 px-3 py-2 text-sm text-slate-600 rounded-lg hover:bg-slate-50 transition-colors"
                    data-nav href="{{ route('users.index') }}">
                    <i class="bi bi-people text-lg"></i>
                    <span>Usuarios</span>
                </a>

                <a class="flex items-center gap-3 px-3 py-2 text-sm text-slate-600 rounded-lg hover:bg-slate-50 transition-colors"
                    data-nav href="{{ route('roles.index') }}">
                    <i class="bi bi-shield-lock text-lg"></i>
                    <span>Roles</span>
                </a>

                <a class="flex items-center gap-3 px-3 py-2 text-sm text-slate-600 rounded-lg hover:bg-slate-50 transition-colors"
                    data-nav href="{{ route('permissions.index') }}">
                    <i class="bi bi-key text-lg"></i>
                    <span>Permisos</span>
                </a>
            </div>
        @endif
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
