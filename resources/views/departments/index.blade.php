@extends('layouts.app')

@section('title', 'Gestionar Departamentos')

@section('content')
<div class="space-y-6">
    <!-- Alerts -->
    @include('components.alerts')

    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-semibold text-slate-900">Gestionar Departamentos</h1>
            <p class="text-sm text-slate-500 mt-1">Administra los departamentos del sistema</p>
        </div>
        @can('create', App\Models\Department::class)
        <a href="{{ route('departments.create') }}"
           class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-lg transition-colors">
            <i class="bi bi-plus-lg"></i>
            <span>Nuevo Departamento</span>
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
                           placeholder="Buscar por nombre..."
                           class="w-full pl-10 pr-4 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none transition">
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla de Departamentos -->
    <div class="bg-white rounded-lg border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-600 uppercase tracking-wider">#</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-600 uppercase tracking-wider">Nombre</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-600 uppercase tracking-wider">Productos</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-600 uppercase tracking-wider">Fecha Creación</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-slate-600 uppercase tracking-wider">Acciones</th>
                    </tr>
                </thead>
                <tbody id="departmentsTable" class="divide-y divide-slate-200">
                    @include('departments.partials.table-rows', ['departments' => $departments])
                </tbody>
            </table>
        </div>
    </div>

    <!-- Paginación -->
    @if($departments->hasPages())
    <div class="flex justify-center">
        {{ $departments->links() }}
    </div>
    @endif
</div>

@endsection

@section('scripts')
<script>
// Búsqueda en tiempo real
const searchInput = document.getElementById('searchInput');
const tableBody = document.getElementById('departmentsTable');

let debounceTimer;

searchInput.addEventListener('input', function() {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        filterDepartments();
    }, 300);
});

function filterDepartments() {
    const search = searchInput.value;

    // Construir URL con parámetros
    const url = new URL('{{ route("departments.index") }}');
    if (search) url.searchParams.append('search', search);

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

function confirmDelete(departmentId) {
    if (confirm('¿Estás seguro de eliminar este departamento?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/departments/${departmentId}`;

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
