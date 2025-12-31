@extends('layouts.app')
@section('title', 'Inventario por Sucursal')
@section('content')
<div class="space-y-6">
    @include('components.alerts')
    <div><h1 class="text-xl font-semibold text-slate-900">Inventario por Sucursal</h1><p class="text-sm text-slate-500 mt-1">Consulta el stock de productos por sucursal</p></div>
    <div class="bg-white rounded-lg border border-slate-200 p-4"><div class="grid grid-cols-1 md:grid-cols-4 gap-3">
        <select id="branchFilter" class="px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none transition">@foreach($branches as $b)<option value="{{ $b->id }}" {{ $branchId == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>@endforeach</select>
        <select id="departmentFilter" class="px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none transition"><option value="">Todos departamentos</option>@foreach($departments as $d)<option value="{{ $d->id }}">{{ $d->name }}</option>@endforeach</select>
        <input type="number" id="lowStockFilter" placeholder="Stock bajo..." class="px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none transition">
        <input type="text" id="searchInput" placeholder="Buscar producto..." class="px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none transition">
    </div></div>
    <div class="bg-white rounded-lg border border-slate-200 overflow-hidden"><table class="w-full"><thead class="bg-slate-50 border-b border-slate-200"><tr><th class="px-4 py-3 text-left text-xs font-medium text-slate-600 uppercase">Producto</th><th class="px-4 py-3 text-left text-xs font-medium text-slate-600 uppercase">Departamento</th><th class="px-4 py-3 text-left text-xs font-medium text-slate-600 uppercase">Stock</th><th class="px-4 py-3 text-left text-xs font-medium text-slate-600 uppercase">Unidad</th></tr></thead><tbody id="inventoryTable" class="divide-y divide-slate-200">@include('inventory.partials.table-rows')</tbody></table></div>
    @if($inventories->hasPages())<div class="flex justify-center">{{ $inventories->links() }}</div>@endif
</div>
@endsection
@section('scripts')
<script>
const filters = {branch: document.getElementById('branchFilter'), department: document.getElementById('departmentFilter'), lowStock: document.getElementById('lowStockFilter'), search: document.getElementById('searchInput')};
let debounceTimer;
Object.values(filters).forEach(f => f.addEventListener(f === filters.search ? 'input' : 'change', () => {clearTimeout(debounceTimer); debounceTimer = setTimeout(filter, 300);}));
function filter() {
    const url = new URL('{{ route("inventory.index") }}');
    if (filters.branch.value) url.searchParams.append('branch', filters.branch.value);
    if (filters.department.value) url.searchParams.append('department', filters.department.value);
    if (filters.lowStock.value) url.searchParams.append('low_stock', filters.lowStock.value);
    if (filters.search.value) url.searchParams.append('search', filters.search.value);
    fetch(url, {headers: {'X-Requested-With': 'XMLHttpRequest'}}).then(r => r.text()).then(html => {document.getElementById('inventoryTable').innerHTML = html;});
}
</script>
@endsection
