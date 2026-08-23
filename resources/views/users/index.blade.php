@extends('layouts.app')

@section('title', 'Gestionar Usuarios')

@section('content')
<div class="space-y-6">
    <!-- Alerts -->
    @include('components.alerts')

    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-semibold text-slate-900">Gestionar Usuarios</h1>
            <p class="text-sm text-slate-500 mt-1">Administra los usuarios del sistema</p>
        </div>
        <a href="{{ route('users.create') }}"
           class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-lg transition-colors">
            <i class="bi bi-plus-lg"></i>
            <span>Nuevo Usuario</span>
        </a>
    </div>

    <!-- Filtros -->
    <div class="bg-white rounded-lg border border-slate-200 p-4">
        <div class="flex flex-col md:flex-row gap-3">
            <!-- Buscador -->
            <div class="flex-1">
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400">
                        <i class="bi bi-search"></i>
                    </span>
                    <input type="text"
                           id="searchInput"
                           placeholder="Buscar por nombre, email o usuario..."
                           class="w-full pl-10 pr-4 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none transition">
                </div>
            </div>

            <!-- Filtro por sucursal -->
            <div class="w-full md:w-48">
                <select id="branchFilter"
                        class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none transition">
                    <option value="">Todas las sucursales</option>
                    @foreach($branches as $branch)
                    <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Filtro por rol -->
            <div class="w-full md:w-48">
                <select id="roleFilter"
                        class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none transition">
                    <option value="">Todos los roles</option>
                    @foreach($roles as $role)
                    <option value="{{ $role->id }}">{{ $role->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Filtro por estado activo -->
            <div class="w-full md:w-40">
                <select id="activeFilter"
                        class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none transition">
                    <option value="">Todos los estados</option>
                    <option value="1">Activos</option>
                    <option value="0">Inactivos</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Tabla de Usuarios -->
    <div class="bg-white rounded-lg border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-600 uppercase tracking-wider">#</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-600 uppercase tracking-wider">Nombre</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-600 uppercase tracking-wider">Usuario</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-600 uppercase tracking-wider">Email</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-600 uppercase tracking-wider">Sucursal</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-600 uppercase tracking-wider">Roles</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-600 uppercase tracking-wider">Estado</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-slate-600 uppercase tracking-wider">Acciones</th>
                    </tr>
                </thead>
                <tbody id="usersTable" class="divide-y divide-slate-200">
                    @include('users.partials.table-rows', ['users' => $users])
                </tbody>
            </table>
        </div>
    </div>

    <!-- Paginación -->
    @if($users->hasPages())
    <div class="flex justify-center">
        {{ $users->links() }}
    </div>
    @endif
</div>

@endsection

@section('scripts')
<script>
// Búsqueda en tiempo real
const searchInput = document.getElementById('searchInput');
const branchFilter = document.getElementById('branchFilter');
const roleFilter = document.getElementById('roleFilter');
const activeFilter = document.getElementById('activeFilter');
const tableBody = document.getElementById('usersTable');

let debounceTimer;

searchInput.addEventListener('input', function() {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        filterUsers();
    }, 300);
});

branchFilter.addEventListener('change', filterUsers);
roleFilter.addEventListener('change', filterUsers);
activeFilter.addEventListener('change', filterUsers);

function filterUsers() {
    const search = searchInput.value;
    const branch = branchFilter.value;
    const role = roleFilter.value;
    const isActive = activeFilter.value;

    // Construir URL con parámetros
    const url = new URL('{{ route("users.index") }}');
    if (search) url.searchParams.append('search', search);
    if (branch) url.searchParams.append('branch', branch);
    if (role) url.searchParams.append('role', role);
    if (isActive) url.searchParams.append('is_active', isActive);

    // AJAX request
    fetch(url, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.text())
    .then(html => {
        // Actualizar solo el tbody
        tableBody.innerHTML = html;
    })
    .catch(error => console.error('Error:', error));
}

function confirmDelete(userId) {
    ConfirmModal.confirmDelete({
        action: `/users/${userId}`,
        title: 'Eliminar usuario',
        message: '¿Estás seguro de eliminar este usuario? Esta acción se puede revertir posteriormente.',
    });
}
</script>
@endsection
