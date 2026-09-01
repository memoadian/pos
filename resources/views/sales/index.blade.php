@extends('layouts.app')
@section('title', 'Ventas')
@section('content')
<div class="space-y-6">
    @include('components.alerts')

    <div class="flex items-center justify-between">
        <div>
            <div class="flex items-center gap-2.5">
                <h1 class="text-xl font-semibold text-slate-900">Ventas</h1>
                @include('components.count-badge', ['count' => $totalSales, 'icon' => 'bi-receipt', 'label' => 'ventas registradas'])
            </div>
            <p class="text-sm text-slate-500 mt-1">Consulta y cancela ventas. Al cancelar se restaura el inventario y se revierten los totales de la caja.</p>
        </div>
    </div>

    <div class="bg-white rounded-lg border border-slate-200 p-4">
        <div class="flex flex-wrap items-end gap-3">
            <div>
                <label for="start_date" class="block text-xs font-medium text-slate-600 mb-1">Desde</label>
                <input type="date" id="start_date" value="{{ $startDate }}"
                    class="px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none">
            </div>
            <div>
                <label for="end_date" class="block text-xs font-medium text-slate-600 mb-1">Hasta</label>
                <input type="date" id="end_date" value="{{ $endDate }}"
                    class="px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none">
            </div>
            @if($branches->count() > 1)
            <div>
                <label for="branchFilter" class="block text-xs font-medium text-slate-600 mb-1">Sucursal</label>
                <select id="branchFilter" class="px-3 py-2 text-sm border border-slate-300 rounded-lg">
                    <option value="">Todas mis sucursales</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" @selected((string) $branchId === (string) $branch->id)>{{ $branch->name }}</option>
                    @endforeach
                </select>
            </div>
            @endif
            <div>
                <label for="statusFilter" class="block text-xs font-medium text-slate-600 mb-1">Estado</label>
                <select id="statusFilter" class="px-3 py-2 text-sm border border-slate-300 rounded-lg">
                    <option value="">Todos</option>
                    <option value="completada" @selected(request('status') === 'completada')>Completadas</option>
                    <option value="cancelada" @selected(request('status') === 'cancelada')>Canceladas</option>
                </select>
            </div>
            <div class="flex-1 min-w-[8rem]">
                <label for="searchInput" class="block text-xs font-medium text-slate-600 mb-1"># Venta</label>
                <input type="text" id="searchInput" value="{{ request('search') }}" placeholder="Ej. 1024"
                    class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none">
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg border border-slate-200 overflow-hidden">
        <div id="salesSummary">{!! $summary !!}</div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-600 uppercase">#</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-600 uppercase">Fecha</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-600 uppercase">Sucursal</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-600 uppercase">Cajero</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-600 uppercase">Cliente</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-600 uppercase">Pago</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-slate-600 uppercase">Total</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-600 uppercase">Estado</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-slate-600 uppercase">Acciones</th>
                    </tr>
                </thead>
                <tbody id="salesTable" class="divide-y divide-slate-200">
                    @include('sales.partials.table-rows')
                </tbody>
            </table>
        </div>
    </div>
    <div class="flex justify-center" id="salesPagination">@if($sales->hasPages()){{ $sales->links() }}@endif</div>
</div>
@endsection
@section('scripts')
<script>
const filters = {
    start: document.getElementById('start_date'),
    end: document.getElementById('end_date'),
    branch: document.getElementById('branchFilter'),
    status: document.getElementById('statusFilter'),
    search: document.getElementById('searchInput'),
};
let debounce;
filters.search.addEventListener('input', () => { clearTimeout(debounce); debounce = setTimeout(filter, 300); });
['start', 'end', 'branch', 'status'].forEach(k => filters[k]?.addEventListener('change', filter));

function filter() {
    const url = new URL('{{ route("sales.index") }}');
    if (filters.start.value) url.searchParams.append('start_date', filters.start.value);
    if (filters.end.value) url.searchParams.append('end_date', filters.end.value);
    if (filters.branch && filters.branch.value) url.searchParams.append('branch', filters.branch.value);
    if (filters.status.value) url.searchParams.append('status', filters.status.value);
    if (filters.search.value) url.searchParams.append('search', filters.search.value);

    fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(r => r.json())
        .then(data => {
            document.getElementById('salesTable').innerHTML = data.rows;
            document.getElementById('salesPagination').innerHTML = data.pagination;
            document.getElementById('salesSummary').innerHTML = data.summary;
            history.replaceState(null, '', url.search || location.pathname);
        })
        .catch(e => console.error('Error:', e));
}
</script>
@endsection
