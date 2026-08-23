@extends('layouts.app')
@section('title', 'Tipos de Venta')
@section('content')
<div class="space-y-6">
    @include('components.alerts')
    <div class="flex items-center justify-between">
        <div>
            <div class="flex items-center gap-2.5">
                <h1 class="text-xl font-semibold text-slate-900">Tipos de Venta</h1>
                @include('components.count-badge', ['count' => $totalSaleTypes, 'icon' => 'bi-rulers', 'label' => 'tipos de venta dados de alta'])
            </div>
            <p class="text-sm text-slate-500 mt-1">Administra los tipos de venta del sistema</p>
        </div>
        @can('create', App\Models\SaleType::class)
        <a href="{{ route('sale-types.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-lg transition-colors"><i class="bi bi-plus-lg"></i><span>Nuevo Tipo</span></a>
        @endcan
    </div>
    <div class="bg-white rounded-lg border border-slate-200 p-4"><div class="flex gap-3">
        <div class="flex-1"><input type="text" id="searchInput" value="{{ request('search') }}" placeholder="Buscar..." class="w-full px-4 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none transition"></div>
        <select id="activeFilter" class="px-3 py-2 text-sm border border-slate-300 rounded-lg"><option value="">Todos</option><option value="1" @selected(request('is_active') === '1')>Activos</option><option value="0" @selected(request('is_active') === '0')>Inactivos</option></select>
    </div></div>
    <div class="bg-white rounded-lg border border-slate-200 overflow-hidden"><div id="typesSummary">{!! $summary !!}</div><table class="w-full"><thead class="bg-slate-50 border-b border-slate-200"><tr><th class="px-4 py-3 text-left text-xs font-medium text-slate-600 uppercase">#</th><th class="px-4 py-3 text-left text-xs font-medium text-slate-600 uppercase">Nombre</th><th class="px-4 py-3 text-left text-xs font-medium text-slate-600 uppercase">Unidad Base</th><th class="px-4 py-3 text-left text-xs font-medium text-slate-600 uppercase">Cantidad</th><th class="px-4 py-3 text-left text-xs font-medium text-slate-600 uppercase">Productos</th><th class="px-4 py-3 text-left text-xs font-medium text-slate-600 uppercase">Estado</th><th class="px-4 py-3 text-right text-xs font-medium text-slate-600 uppercase">Acciones</th></tr></thead><tbody id="typesTable" class="divide-y divide-slate-200">@include('sale-types.partials.table-rows')</tbody></table></div>
    <div class="flex justify-center" id="typesPagination">@if($saleTypes->hasPages()){{ $saleTypes->links() }}@endif</div>
</div>
@endsection
@section('scripts')
<script>
const filters={search:document.getElementById('searchInput'),active:document.getElementById('activeFilter')};
let debounce;
filters.search.addEventListener('input',()=>{clearTimeout(debounce);debounce=setTimeout(filter,300);});
filters.active.addEventListener('change',filter);
function filter() {
    const url = new URL('{{ route("sale-types.index") }}');
    if (filters.search.value) url.searchParams.append('search', filters.search.value);
    if (filters.active.value) url.searchParams.append('is_active', filters.active.value);

    fetch(url, {headers: {'X-Requested-With': 'XMLHttpRequest'}})
        .then(r => r.json())
        .then(data => {
            document.getElementById('typesTable').innerHTML = data.rows;
            document.getElementById('typesPagination').innerHTML = data.pagination;
            document.getElementById('typesSummary').innerHTML = data.summary;
            // La URL se sincroniza para que recargar conserve los filtros.
            history.replaceState(null, '', url.search || location.pathname);
        })
        .catch(e => console.error('Error:', e));
}
function confirmDelete(id){
    ConfirmModal.confirmDelete({
        action: `/sale-types/${id}`,
        title: 'Eliminar tipo de venta',
        message: '¿Deseas eliminar este tipo de venta? Esta acción no se puede deshacer.',
    });
}
</script>
@endsection
