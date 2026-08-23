@extends('layouts.app')

@section('title', 'Gestionar Sucursales')

@section('content')
<div class="space-y-6">
    <!-- Alerts -->
    @include('components.alerts')

    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-semibold text-slate-900">Gestionar Sucursales</h1>
            <p class="text-sm text-slate-500 mt-1">Administra las sucursales del sistema</p>
        </div>
        @can('create', App\Models\Branch::class)
        <a href="{{ route('branches.create') }}"
           class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-lg transition-colors">
            <i class="bi bi-plus-lg"></i>
            <span>Nueva Sucursal</span>
        </a>
        @endcan
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
                           placeholder="Buscar por nombre o dirección..."
                           class="w-full pl-10 pr-4 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none transition">
                </div>
            </div>

            <!-- Filtro por estado activo -->
            <div class="w-full md:w-40">
                <select id="activeFilter"
                        class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none transition">
                    <option value="">Todos los estados</option>
                    <option value="1">Activas</option>
                    <option value="0">Inactivas</option>
                </select>
            </div>

            <!-- Filtro por eliminados -->
            <div class="w-full md:w-40">
                <select id="deletedFilter"
                        class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none transition">
                    <option value="">Solo activos</option>
                    <option value="with">Incluir eliminados</option>
                    <option value="only">Solo eliminados</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Tabla de Sucursales -->
    <div class="bg-white rounded-lg border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-600 uppercase tracking-wider">#</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-600 uppercase tracking-wider">Nombre</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-600 uppercase tracking-wider">Dirección</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-600 uppercase tracking-wider">Usuarios</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-600 uppercase tracking-wider">Estado</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-slate-600 uppercase tracking-wider">Acciones</th>
                    </tr>
                </thead>
                <tbody id="branchesTable" class="divide-y divide-slate-200">
                    @include('branches.partials.table-rows', ['branches' => $branches])
                </tbody>
            </table>
        </div>
    </div>

    <!-- Paginación -->
    @if($branches->hasPages())
    <div class="flex justify-center">
        {{ $branches->links() }}
    </div>
    @endif
</div>

@endsection

@section('scripts')
<script>
// Búsqueda en tiempo real
const searchInput = document.getElementById('searchInput');
const activeFilter = document.getElementById('activeFilter');
const deletedFilter = document.getElementById('deletedFilter');
const tableBody = document.getElementById('branchesTable');

let debounceTimer;

searchInput.addEventListener('input', function() {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        filterBranches();
    }, 300);
});

activeFilter.addEventListener('change', filterBranches);
deletedFilter.addEventListener('change', filterBranches);

function filterBranches() {
    const search = searchInput.value;
    const isActive = activeFilter.value;
    const showDeleted = deletedFilter.value;

    // Construir URL con parámetros
    const url = new URL('{{ route("branches.index") }}');
    if (search) url.searchParams.append('search', search);
    if (isActive) url.searchParams.append('is_active', isActive);
    if (showDeleted) url.searchParams.append('show_deleted', showDeleted);

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

function confirmDelete(branchId) {
    ConfirmModal.confirmDelete({
        action: `/branches/${branchId}`,
        title: 'Eliminar sucursal',
        message: '¿Estás seguro de eliminar esta sucursal? Esta acción se puede revertir posteriormente.',
    });
}

function confirmRestore(branchId) {
    ConfirmModal.show({
        title: 'Restaurar sucursal',
        message: '¿Estás seguro de restaurar esta sucursal?',
        confirmText: 'Restaurar',
        danger: false,
        onConfirm: () => { window.location.href = `/branches/${branchId}/restore`; },
    });
}
</script>
@endsection
