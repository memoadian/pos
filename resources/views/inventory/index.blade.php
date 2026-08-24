@extends('layouts.app')
@section('title', 'Inventario por Sucursal')
@section('content')
<div class="space-y-6">
    @include('components.alerts')
    <div>
        <div class="flex items-center gap-2.5">
            <h1 class="text-xl font-semibold text-slate-900">Inventario por Sucursal</h1>
            @include('components.count-badge', ['count' => $totalInventories, 'icon' => 'bi-boxes', 'label' => 'productos en el inventario de esta sucursal'])
        </div>
        <p class="text-sm text-slate-500 mt-1">Consulta el stock de productos por sucursal</p>
    </div>
    <div class="bg-white rounded-lg border border-slate-200 p-4"><div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-3">
        <select id="departmentFilter" class="px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none transition"><option value="">Todos departamentos</option>@foreach($departments as $d)<option value="{{ $d->id }}" @selected((int) request('department') === $d->id)>{{ $d->name }}</option>@endforeach</select>
        <label class="flex items-center gap-2 px-3 py-2 text-sm border border-slate-300 rounded-lg cursor-pointer hover:bg-slate-50 transition-colors"><input type="checkbox" id="inStockFilter" value="1" @checked(request()->boolean('in_stock')) class="w-4 h-4 text-cyan-600 border-slate-300 rounded focus:ring-2 focus:ring-cyan-500"><span class="text-slate-700">Solo con stock</span></label>
        <label class="flex items-center gap-2 px-3 py-2 text-sm border border-slate-300 rounded-lg cursor-pointer hover:bg-slate-50 transition-colors"><input type="checkbox" id="lowStockFilter" value="1" @checked(request()->boolean('low_stock')) class="w-4 h-4 text-cyan-600 border-slate-300 rounded focus:ring-2 focus:ring-cyan-500"><span class="text-slate-700">Solo stock bajo</span></label>
        <input type="text" id="searchInput" value="{{ request('search') }}" placeholder="Buscar producto..." class="px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none transition">
    </div></div>
    <div class="bg-white rounded-lg border border-slate-200 overflow-hidden"><div id="inventorySummary">{!! $summary !!}</div><table class="w-full"><thead class="bg-slate-50 border-b border-slate-200"><tr><th class="px-4 py-3 text-left text-xs font-medium text-slate-600 uppercase">Producto</th><th class="px-4 py-3 text-left text-xs font-medium text-slate-600 uppercase">Departamento</th><th class="px-4 py-3 text-left text-xs font-medium text-slate-600 uppercase">Stock</th><th class="px-4 py-3 text-left text-xs font-medium text-slate-600 uppercase">Unidad</th></tr></thead><tbody id="inventoryTable" class="divide-y divide-slate-200">@include('inventory.partials.table-rows')</tbody></table></div>
    <div class="flex justify-center" id="inventoryPagination">@if($inventories->hasPages()){{ $inventories->links() }}@endif</div>
</div>
@endsection
@section('scripts')
<script>
const filters = {department: document.getElementById('departmentFilter'), inStock: document.getElementById('inStockFilter'), lowStock: document.getElementById('lowStockFilter'), search: document.getElementById('searchInput')};
let debounceTimer;
Object.values(filters).forEach(f => f.addEventListener(f === filters.search ? 'input' : 'change', () => {clearTimeout(debounceTimer); debounceTimer = setTimeout(filter, 300);}));
function filter() {
    const url = new URL('{{ route("inventory.index") }}');
    if (filters.department.value) url.searchParams.append('department', filters.department.value);
    if (filters.inStock.checked) url.searchParams.append('in_stock', '1');
    if (filters.lowStock.checked) url.searchParams.append('low_stock', '1');
    if (filters.search.value) url.searchParams.append('search', filters.search.value);

    fetch(url, {headers: {'X-Requested-With': 'XMLHttpRequest'}})
        .then(r => r.json())
        .then(data => {
            document.getElementById('inventoryTable').innerHTML = data.rows;
            document.getElementById('inventoryPagination').innerHTML = data.pagination;
            document.getElementById('inventorySummary').innerHTML = data.summary;
            // La URL se sincroniza para que recargar conserve los filtros.
            history.replaceState(null, '', url.search || location.pathname);
        })
        .catch(e => console.error('Error:', e));
}
</script>
@endsection
