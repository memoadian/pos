@extends('layouts.app')
@section('title', 'Gestionar Productos')
@section('content')
<div class="space-y-6">
    @include('components.alerts')
    <div class="flex items-center justify-between">
        <div>
            <div class="flex items-center gap-2.5">
                <h1 class="text-xl font-semibold text-slate-900">Gestionar Productos</h1>
                @include('components.count-badge', ['count' => $totalProducts, 'icon' => 'bi-box-seam', 'label' => 'productos en el catálogo'])
            </div>
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
            <div class="flex-1"><div class="relative"><span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400"><i class="bi bi-search"></i></span><input type="text" id="searchInput" value="{{ request('search') }}" placeholder="Buscar por nombre o código..." class="w-full pl-10 pr-4 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none transition"></div></div>
            <select id="departmentFilter" class="w-full md:w-48 px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none transition"><option value="">Todos los departamentos</option>@foreach($departments as $dept)<option value="{{ $dept->id }}" @selected((int) request('department') === $dept->id)>{{ $dept->name }}</option>@endforeach</select>
            <select id="activeFilter" class="w-full md:w-40 px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none transition"><option value="">Todos</option><option value="1" @selected(request('is_active') === '1')>Activos</option><option value="0" @selected(request('is_active') === '0')>Inactivos</option></select>
            <select id="perPageFilter" class="w-full md:w-40 px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none transition" title="Productos por página">
                @foreach(App\Http\Controllers\ProductController::PER_PAGE_OPTIONS as $option)
                <option value="{{ $option }}" @selected($perPage === $option)>Mostrar {{ $option }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="bg-white rounded-lg border border-slate-200 p-3 flex flex-wrap gap-1" id="letterFilter">
        <button type="button" data-letter="" class="letter-btn px-2 py-1 text-xs font-medium rounded {{ request('letter') === null || request('letter') === '' ? 'bg-cyan-600 text-white' : 'text-slate-600 hover:bg-slate-100' }}">Todos</button>
        <button type="button" data-letter="#" class="letter-btn px-2 py-1 text-xs font-medium rounded {{ request('letter') === '#' ? 'bg-cyan-600 text-white' : 'text-slate-600 hover:bg-slate-100' }}">#</button>
        @foreach(range('A', 'Z') as $letter)
        <button type="button" data-letter="{{ $letter }}" class="letter-btn px-2 py-1 text-xs font-medium rounded {{ request('letter') === $letter ? 'bg-cyan-600 text-white' : 'text-slate-600 hover:bg-slate-100' }}">{{ $letter }}</button>
        @endforeach
    </div>
    <div class="bg-white rounded-lg border border-slate-200 overflow-hidden"><div id="productsSummary">{!! $summary !!}</div><div class="overflow-x-auto"><table class="w-full"><thead class="bg-slate-50 border-b border-slate-200"><tr><th class="px-4 py-3 text-left text-xs font-medium text-slate-600 uppercase tracking-wider">#</th><th class="px-4 py-3 text-left text-xs font-medium text-slate-600 uppercase tracking-wider">Código</th><th class="px-4 py-3 text-left text-xs font-medium text-slate-600 uppercase tracking-wider">Nombre</th><th class="px-4 py-3 text-left text-xs font-medium text-slate-600 uppercase tracking-wider">Departamento</th><th class="px-4 py-3 text-left text-xs font-medium text-slate-600 uppercase tracking-wider">Precio</th><th class="px-4 py-3 text-left text-xs font-medium text-slate-600 uppercase tracking-wider">Estado</th><th class="px-4 py-3 text-right text-xs font-medium text-slate-600 uppercase tracking-wider">Acciones</th></tr></thead><tbody id="productsTable" class="divide-y divide-slate-200">@include('products.partials.table-rows')</tbody></table></div></div>
    <div class="flex justify-center" id="productsPagination">@if($products->hasPages()){{ $products->links() }}@endif</div>
</div>
@endsection
@section('scripts')
<script>
const searchInput = document.getElementById('searchInput');
const departmentFilter = document.getElementById('departmentFilter');
const activeFilter = document.getElementById('activeFilter');
const perPageFilter = document.getElementById('perPageFilter');
const tableBody = document.getElementById('productsTable');
const pagination = document.getElementById('productsPagination');
const summary = document.getElementById('productsSummary');
let debounceTimer;
let activeLetter = '{{ request('letter') }}';
searchInput.addEventListener('input', ()=> {clearTimeout(debounceTimer); debounceTimer = setTimeout(filterProducts, 300);});
departmentFilter.addEventListener('change', filterProducts);
activeFilter.addEventListener('change', filterProducts);
perPageFilter.addEventListener('change', filterProducts);
document.getElementById('letterFilter').addEventListener('click', (e) => {
    const btn = e.target.closest('.letter-btn');
    if (!btn) return;
    activeLetter = btn.dataset.letter;
    document.querySelectorAll('.letter-btn').forEach(b => {
        b.classList.toggle('bg-cyan-600', b === btn);
        b.classList.toggle('text-white', b === btn);
        b.classList.toggle('text-slate-600', b !== btn);
    });
    filterProducts();
});
function filterProducts() {
    const url = new URL('{{ route("products.index") }}');
    if (searchInput.value) url.searchParams.append('search', searchInput.value);
    if (departmentFilter.value) url.searchParams.append('department', departmentFilter.value);
    if (activeFilter.value) url.searchParams.append('is_active', activeFilter.value);
    if (activeLetter) url.searchParams.append('letter', activeLetter);
    url.searchParams.append('per_page', perPageFilter.value);
    // Cambiar de filtro manda a la pagina 1: la que se estaba viendo puede ya no existir.
    fetch(url, {headers: {'X-Requested-With': 'XMLHttpRequest'}})
        .then(r => r.json())
        .then(data => {
            tableBody.innerHTML = data.rows;
            pagination.innerHTML = data.pagination;
            summary.innerHTML = data.summary;
            // La URL se sincroniza para que recargar o compartir el link conserve los filtros.
            history.replaceState(null, '', url.search);
        })
        .catch(e => console.error('Error:', e));
}
function confirmDelete(id) {
    ConfirmModal.confirmDelete({
        action: `/products/${id}`,
        title: 'Desactivar producto',
        message: '¿Desactivar este producto? Dejará de aparecer en el punto de venta.',
        confirmText: 'Desactivar',
    });
}
</script>
@endsection
