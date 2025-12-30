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
        <a href="{{ route('dashboard') }}" data-nav class="flex items-center gap-3 px-3 py-2 text-sm text-slate-600 rounded-lg hover:bg-slate-50 transition-colors">
            <i class="bi bi-grid-1x2 text-lg"></i>
            <span>Dashboard</span>
        </a>

        <a href="#" data-nav class="flex items-center gap-3 px-3 py-2 text-sm text-slate-600 rounded-lg hover:bg-slate-50 transition-colors">
            <i class="bi bi-cart3 text-lg"></i>
            <span>Ventas</span>
        </a>

        <a href="#" data-nav class="flex items-center gap-3 px-3 py-2 text-sm text-slate-600 rounded-lg hover:bg-slate-50 transition-colors">
            <i class="bi bi-box-seam text-lg"></i>
            <span>Productos</span>
        </a>

        <a href="#" data-nav class="flex items-center gap-3 px-3 py-2 text-sm text-slate-600 rounded-lg hover:bg-slate-50 transition-colors">
            <i class="bi bi-boxes text-lg"></i>
            <span>Inventario</span>
        </a>

        @if(auth()->user()->role === 'cajero')
        <a href="#" data-nav class="flex items-center gap-3 px-3 py-2 text-sm text-slate-600 rounded-lg hover:bg-slate-50 transition-colors">
            <i class="bi bi-cash-stack text-lg"></i>
            <span>Mi Caja</span>
        </a>
        @endif

        @if(in_array(auth()->user()->role, ['supervisor', 'gerente', 'admin']))
        <div class="pt-4">
            <p class="px-3 text-xs font-medium text-slate-400 uppercase tracking-wider mb-2">Gestión</p>

            <a href="#" data-nav class="flex items-center gap-3 px-3 py-2 text-sm text-slate-600 rounded-lg hover:bg-slate-50 transition-colors">
                <i class="bi bi-graph-up text-lg"></i>
                <span>Reportes</span>
            </a>
        </div>
        @endif

        @if(in_array(auth()->user()->role, ['gerente', 'admin']))
        <a href="#" data-nav class="flex items-center gap-3 px-3 py-2 text-sm text-slate-600 rounded-lg hover:bg-slate-50 transition-colors">
            <i class="bi bi-people text-lg"></i>
            <span>Usuarios</span>
        </a>
        @endif

        @if(auth()->user()->role === 'admin')
        <div class="pt-4">
            <p class="px-3 text-xs font-medium text-slate-400 uppercase tracking-wider mb-2">Admin</p>

            <a href="#" data-nav class="flex items-center gap-3 px-3 py-2 text-sm text-slate-600 rounded-lg hover:bg-slate-50 transition-colors">
                <i class="bi bi-building text-lg"></i>
                <span>Sucursales</span>
            </a>

            <a href="#" data-nav class="flex items-center gap-3 px-3 py-2 text-sm text-slate-600 rounded-lg hover:bg-slate-50 transition-colors">
                <i class="bi bi-tags text-lg"></i>
                <span>Categorías</span>
            </a>
        </div>
        @endif
    </nav>

    <!-- Footer -->
    <div class="p-3 border-t border-slate-200 flex-shrink-0">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="w-full flex items-center gap-3 px-3 py-2 text-sm text-slate-600 rounded-lg hover:bg-red-50 hover:text-red-600 transition-colors">
                <i class="bi bi-box-arrow-left text-lg"></i>
                <span>Cerrar Sesión</span>
            </button>
        </form>
    </div>
</div>
