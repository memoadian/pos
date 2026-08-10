@extends('layouts.app')
@section('title', 'Gestionar Productos')
@section('content')
<div class="space-y-6">
    @include('components.alerts')
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-semibold text-slate-900">Gestionar Productos</h1>
            <p class="text-sm text-slate-500 mt-1">Administra el catálogo de productos</p>
        </div>
        @can('create', App\Models\Product::class)
        <div class="flex items-center gap-2">
            <a href="{{ route('products.import.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-medium rounded-lg transition-colors">
                <i class="bi bi-upload"></i><span>Importar</span>
            </a>
            <a href="{{ route('products.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-lg transition-colors">
                <i class="bi bi-plus-lg"></i><span>Nuevo Producto</span>
            </a>
        </div>
        @endcan
    </div>
    <div class="bg-white rounded-lg border border-slate-200 p-4">
        <div class="flex flex-col md:flex-row gap-3">
            <div class="flex-1"><div class="relative"><span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400"><i class="bi bi-search"></i></span><input type="text" id="searchInput" placeholder="Buscar por nombre o código..." class="w-full pl-10 pr-4 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none transition"></div></div>
            <select id="departmentFilter" class="w-full md:w-48 px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none transition"><option value="">Todos los departamentos</option>@foreach($departments as $dept)<option value="{{ $dept->id }}">{{ $dept->name }}</option>@endforeach</select>
            <select id="activeFilter" class="w-full md:w-40 px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none transition"><option value="">Todos</option><option value="1">Activos</option><option value="0">Inactivos</option></select>
        </div>
    </div>
    <div class="bg-white rounded-lg border border-slate-200 overflow-hidden"><div class="overflow-x-auto"><table class="w-full"><thead class="bg-slate-50 border-b border-slate-200"><tr><th class="px-4 py-3 text-left text-xs font-medium text-slate-600 uppercase tracking-wider">#</th><th class="px-4 py-3 text-left text-xs font-medium text-slate-600 uppercase tracking-wider">Código</th><th class="px-4 py-3 text-left text-xs font-medium text-slate-600 uppercase tracking-wider">Nombre</th><th class="px-4 py-3 text-left text-xs font-medium text-slate-600 uppercase tracking-wider">Departamento</th><th class="px-4 py-3 text-left text-xs font-medium text-slate-600 uppercase tracking-wider">Precio</th><th class="px-4 py-3 text-left text-xs font-medium text-slate-600 uppercase tracking-wider">Estado</th><th class="px-4 py-3 text-right text-xs font-medium text-slate-600 uppercase tracking-wider">Acciones</th></tr></thead><tbody id="productsTable" class="divide-y divide-slate-200">@include('products.partials.table-rows')</tbody></table></div></div>
    @if($products->hasPages())<div class="flex justify-center">{{ $products->links() }}</div>@endif
</div>
@endsection
@section('scripts')
<script>
const searchInput = document.getElementById('searchInput');
const departmentFilter = document.getElementById('departmentFilter');
const activeFilter = document.getElementById('activeFilter');
const tableBody = document.getElementById('productsTable');
let debounceTimer;
searchInput.addEventListener('input', ()=> {clearTimeout(debounceTimer); debounceTimer = setTimeout(filterProducts, 300);});
departmentFilter.addEventListener('change', filterProducts);
activeFilter.addEventListener('change', filterProducts);
function filterProducts() {
    const url = new URL('{{ route("products.index") }}');
    if (searchInput.value) url.searchParams.append('search', searchInput.value);
    if (departmentFilter.value) url.searchParams.append('department', departmentFilter.value);
    if (activeFilter.value) url.searchParams.append('is_active', activeFilter.value);
    fetch(url, {headers: {'X-Requested-With': 'XMLHttpRequest'}}).then(r => r.text()).then(html => {tableBody.innerHTML = html;}).catch(e => console.error('Error:', e));
}
function confirmDelete(id) {
    if (confirm('¿Desactivar este producto?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/products/${id}`;
        form.innerHTML = `<input type="hidden" name="_token" value="${document.querySelector('meta[name="csrf-token"]').content}"><input type="hidden" name="_method" value="DELETE">`;
        document.body.appendChild(form);
        form.submit();
    }
}
</script>
@endsection
