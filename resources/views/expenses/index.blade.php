@extends('layouts.app')
@section('title', 'Gastos')
@section('content')
<div class="space-y-6">
    @include('components.alerts')

    <div class="flex items-center justify-between">
        <div>
            <div class="flex items-center gap-2.5">
                <h1 class="text-xl font-semibold text-slate-900">Gastos</h1>
                @include('components.count-badge', ['count' => $totalExpenses, 'icon' => 'bi-cash-stack', 'label' => 'gastos registrados'])
            </div>
            <p class="text-sm text-slate-500 mt-1">Gastos operativos por período. Se registran desde <span class="font-medium">Mi Caja</span> y bajan la utilidad neta en Reportes.</p>
        </div>
        <div class="text-right">
            <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Total del período</p>
            <p class="text-2xl font-semibold text-red-600" id="periodTotal">{{ money($periodTotal) }}</p>
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
                <label for="categoryFilter" class="block text-xs font-medium text-slate-600 mb-1">Categoría</label>
                <select id="categoryFilter" class="px-3 py-2 text-sm border border-slate-300 rounded-lg">
                    <option value="">Todas</option>
                    @foreach(\App\Models\Expense::CATEGORIES as $slug => $label)
                        <option value="{{ $slug }}" @selected($category === $slug)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg border border-slate-200 overflow-hidden">
        <div id="expensesSummary">{!! $summary !!}</div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-600 uppercase">#</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-600 uppercase">Fecha</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-600 uppercase">Sucursal</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-600 uppercase">Caja</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-600 uppercase">Usuario</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-600 uppercase">Categoría</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-600 uppercase">Descripción</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-slate-600 uppercase">Monto</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-slate-600 uppercase">Acciones</th>
                    </tr>
                </thead>
                <tbody id="expensesTable" class="divide-y divide-slate-200">
                    @include('expenses.partials.table-rows')
                </tbody>
            </table>
        </div>
    </div>
    <div class="flex justify-center" id="expensesPagination">@if($expenses->hasPages()){{ $expenses->links() }}@endif</div>
</div>
@endsection
@section('scripts')
<script>
const filters = {
    start: document.getElementById('start_date'),
    end: document.getElementById('end_date'),
    branch: document.getElementById('branchFilter'),
    category: document.getElementById('categoryFilter'),
};
['start', 'end', 'branch', 'category'].forEach(k => filters[k]?.addEventListener('change', filter));

function filter() {
    const url = new URL('{{ route("expenses.index") }}');
    if (filters.start.value) url.searchParams.append('start_date', filters.start.value);
    if (filters.end.value) url.searchParams.append('end_date', filters.end.value);
    if (filters.branch && filters.branch.value) url.searchParams.append('branch', filters.branch.value);
    if (filters.category.value) url.searchParams.append('category', filters.category.value);

    fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(r => r.json())
        .then(data => {
            document.getElementById('expensesTable').innerHTML = data.rows;
            document.getElementById('expensesPagination').innerHTML = data.pagination;
            document.getElementById('expensesSummary').innerHTML = data.summary;
            document.getElementById('periodTotal').textContent = data.period_total;
            history.replaceState(null, '', url.search || location.pathname);
        })
        .catch(e => console.error('Error:', e));
}
</script>
@endsection
