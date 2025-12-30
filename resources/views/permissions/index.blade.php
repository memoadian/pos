@extends('layouts.app')

@section('title', 'Gestionar Permisos')

@section('content')
<div class="space-y-6">
    <!-- Alerts -->
    @include('components.alerts')

    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-semibold text-slate-900">Gestionar Permisos</h1>
            <p class="text-sm text-slate-500 mt-1">Administra los permisos del sistema</p>
        </div>
        <a href="{{ route('permissions.create') }}"
           class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-lg transition-colors">
            <i class="bi bi-plus-lg"></i>
            <span>Nuevo Permiso</span>
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
                           placeholder="Buscar permisos..."
                           class="w-full pl-10 pr-4 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none transition">
                </div>
            </div>

            <!-- Filtro por grupo -->
            <div class="w-full md:w-48">
                <select id="groupFilter"
                        class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none transition">
                    <option value="">Todos los grupos</option>
                    @foreach($groups as $group)
                    <option value="{{ $group }}">{{ $group }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <!-- Tabla de Permisos -->
    <div class="bg-white rounded-lg border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-600 uppercase tracking-wider">#</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-600 uppercase tracking-wider">Nombre</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-600 uppercase tracking-wider">Descripción</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-600 uppercase tracking-wider">Grupo</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-600 uppercase tracking-wider">Uso en Código</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-slate-600 uppercase tracking-wider">Acciones</th>
                    </tr>
                </thead>
                <tbody id="permissionsTable" class="divide-y divide-slate-200">
                    @include('permissions.partials.table-rows', ['permissions' => $permissions])
                </tbody>
            </table>
        </div>
    </div>

    <!-- Paginación si es necesario -->
    @if($permissions->hasPages())
    <div class="flex justify-center">
        {{ $permissions->links() }}
    </div>
    @endif
</div>

@endsection

@section('scripts')
<script>
// Búsqueda en tiempo real
const searchInput = document.getElementById('searchInput');
const groupFilter = document.getElementById('groupFilter');
const tableBody = document.getElementById('permissionsTable');

let debounceTimer;

searchInput.addEventListener('input', function() {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        filterPermissions();
    }, 300);
});

groupFilter.addEventListener('change', filterPermissions);

function filterPermissions() {
    const search = searchInput.value;
    const group = groupFilter.value;

    // Construir URL con parámetros
    const url = new URL('{{ route("permissions.index") }}');
    if (search) url.searchParams.append('search', search);
    if (group) url.searchParams.append('group', group);

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

function showUsage(permissionId) {
    // Mostrar ejemplos en nueva ventana
    window.open(`/permissions/${permissionId}/usage`, '_blank', 'width=900,height=700');
}

function confirmDelete(permissionId) {
    if (confirm('¿Estás seguro de eliminar este permiso? Esta acción no se puede deshacer.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/permissions/${permissionId}`;

        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
        form.innerHTML = `
            <input type="hidden" name="_token" value="${csrfToken}">
            <input type="hidden" name="_method" value="DELETE">
        `;

        document.body.appendChild(form);
        form.submit();
    }
}
</script>
@endsection
