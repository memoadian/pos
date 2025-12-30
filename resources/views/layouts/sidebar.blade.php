<!-- Logo/Branding -->
<div class="p-4 border-b border-slate-200 flex-shrink-0">
    <div class="flex items-center gap-3">
        <div class="h-10 w-10 bg-gradient-to-br from-cyan-400 to-sky-500 rounded-lg flex items-center justify-center flex-shrink-0">
            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
        </div>
        <div class="flex-1">
            <h1 class="font-bold text-slate-900">POS</h1>
            <p class="text-xs text-slate-500 sidebar-label">Limpieza</p>
        </div>
    </div>
</div>

<!-- Navigation -->
<nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1">
    <!-- Dashboard -->
    <a href="{{ route('dashboard') }}" class="sidebar-link sidebar-link-item" href="">
        <i class="bi bi-speedometer2 text-lg"></i>
        <span>Dashboard</span>
    </a>

    <!-- Ventas -->
    <a href="#" class="sidebar-link sidebar-link-item" href="">
        <i class="bi bi-cart-fill text-lg"></i>
        <span>Ventas</span>
    </a>

    <!-- Productos -->
    <a href="#" class="sidebar-link sidebar-link-item" href="">
        <i class="bi bi-box-seam-fill text-lg"></i>
        <span>Productos</span>
    </a>

    <!-- Inventario -->
    <a href="#" class="sidebar-link sidebar-link-item" href="">
        <i class="bi bi-boxes text-lg"></i>
        <span>Inventario</span>
    </a>

    <!-- Caja (Cajeros) -->
    @if(auth()->user()->role === 'cajero')
        <a href="#" class="sidebar-link sidebar-link-item" href="">
            <i class="bi bi-cash-stack text-lg"></i>
            <span>Mi Caja</span>
        </a>
    @endif

    <!-- Separador para usuarios con rol elevado -->
    @if(in_array(auth()->user()->role, ['supervisor', 'gerente', 'admin']))
        <hr class="border-slate-200 my-2">
    @endif

    <!-- Reportes (Supervisor+) -->
    @if(in_array(auth()->user()->role, ['supervisor', 'gerente', 'admin']))
        <a href="#" class="sidebar-link sidebar-link-item" href="">
            <i class="bi bi-graph-up text-lg"></i>
            <span>Reportes</span>
        </a>
    @endif

    <!-- Usuarios (Gerente+) -->
    @if(in_array(auth()->user()->role, ['gerente', 'admin']))
        <a href="#" class="sidebar-link sidebar-link-item" href="">
            <i class="bi bi-people-fill text-lg"></i>
            <span>Usuarios</span>
        </a>
    @endif

    <!-- Configuración (Admin) -->
    @if(auth()->user()->role === 'admin')
        <hr class="border-slate-200 my-2">
        <p class="text-xs text-slate-400 px-3 py-2 uppercase tracking-wider sidebar-label font-semibold">Administración</p>

        <a href="#" class="sidebar-link sidebar-link-item" href="">
            <i class="bi bi-building text-lg"></i>
            <span>Sucursales</span>
        </a>

        <a href="#" class="sidebar-link sidebar-link-item" href="">
            <i class="bi bi-tag-fill text-lg"></i>
            <span>Categorías</span>
        </a>
    @endif

    <!-- Footer actions -->
    <hr class="border-slate-200 my-2 mt-4">

    <a href="#" class="sidebar-link sidebar-link-item" id="collapse-toggle" onclick="toggleSidebarCollapse(event)">
        <i class="bi bi-arrows-collapse-vertical text-lg"></i>
        <span>Colapsar</span>
    </a>

    <form method="POST" action="{{ route('logout') }}" class="w-full">
        @csrf
        <button type="submit" class="sidebar-link sidebar-link-item w-full text-left">
            <i class="bi bi-box-arrow-left text-lg"></i>
            <span>Cerrar Sesión</span>
        </button>
    </form>
</nav>

<!-- User Footer -->
<div class="h-16 border-t border-slate-200 px-3 py-3 flex items-center flex-shrink-0 user-footer">
    <div class="h-10 w-10 rounded-full bg-gradient-to-br from-cyan-400 to-sky-500 flex-shrink-0"></div>
    <div class="ml-3 text-xs flex-1">
        <p class="font-semibold text-slate-900">{{ auth()->user()->name }}</p>
        <p class="text-slate-500 capitalize">{{ auth()->user()->role }}</p>
    </div>
</div>
