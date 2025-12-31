@extends('layouts.app')
@section('title', 'Movimientos de Inventario')
@section('content')
    <div class="space-y-6">
        @include('components.alerts')
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-semibold text-slate-900">Movimientos de Inventario</h1>
                <p class="text-sm text-slate-500 mt-1">Historial de entradas, salidas y ajustes</p>
            </div>
            @if (auth()->user()->hasRole(['Admin', 'Admin']))
                <a class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-lg transition-colors"
                    href="{{ route('inventory-movements.create') }}"><i class="bi bi-plus-lg"></i><span>Nuevo
                        Movimiento</span></a>
            @endif
        </div>
        <div class="bg-white rounded-lg border border-slate-200 p-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                <select class="px-3 py-2 text-sm border border-slate-300 rounded-lg" id="branchFilter">
                    <option value="">Todas sucursales</option>
                    @foreach ($branches as $b)
                        <option value="{{ $b->id }}">{{ $b->name }}</option>
                    @endforeach
                </select>
                <select class="px-3 py-2 text-sm border border-slate-300 rounded-lg" id="typeFilter">
                    <option value="">Todos tipos</option>
                    <option value="IN">Entrada</option>
                    <option value="OUT">Salida</option>
                    <option value="ADJUST">Ajuste</option>
                </select>
                <input class="px-3 py-2 text-sm border border-slate-300 rounded-lg" id="dateFromFilter" type="date">
                <input class="px-3 py-2 text-sm border border-slate-300 rounded-lg" id="dateToFilter" type="date">
            </div>
        </div>
        <div class="bg-white rounded-lg border border-slate-200 overflow-hidden">
            <table class="w-full">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-600 uppercase">Fecha</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-600 uppercase">Tipo</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-600 uppercase">Producto</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-600 uppercase">Sucursal</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-600 uppercase">Cantidad</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-600 uppercase">Motivo</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-600 uppercase">Usuario</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200" id="movementsTable">@include('inventory-movements.partials.table-rows')</tbody>
            </table>
        </div>
        @if ($movements->hasPages())
            <div class="flex justify-center">{{ $movements->links() }}</div>
        @endif
    </div>
@endsection
@section('scripts')
    <script>
        const filters = {
            branch: document.getElementById('branchFilter'),
            type: document.getElementById('typeFilter'),
            dateFrom: document.getElementById('dateFromFilter'),
            dateTo: document.getElementById('dateToFilter')
        };
        Object.values(filters).forEach(f => f.addEventListener('change', filter));

        function filter() {
            const url = new URL('{{ route('inventory-movements.index') }}');
            if (filters.branch.value) url.searchParams.append('branch', filters.branch.value);
            if (filters.type.value) url.searchParams.append('type', filters.type.value);
            if (filters.dateFrom.value) url.searchParams.append('date_from', filters.dateFrom.value);
            if (filters.dateTo.value) url.searchParams.append('date_to', filters.dateTo.value);
            fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            }).then(r => r.text()).then(html => {
                document.getElementById('movementsTable').innerHTML = html;
            });
        }
    </script>
@endsection
