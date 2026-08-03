@extends('layouts.app')
@section('title', 'Reportes')
@section('content')
<div class="space-y-6">
    @include('components.alerts')

    <div>
        <h1 class="text-xl font-semibold text-slate-900">Reportes</h1>
        <p class="text-sm text-slate-500 mt-1">Ventas por período</p>
    </div>

    {{-- Filtros --}}
    <form method="GET" action="{{ route('reports.index') }}" id="reportFilterForm" class="bg-white rounded-lg border border-slate-200 p-4 space-y-4">
        @if($branches->count() > 1)
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Sucursal</label>
                <select name="branch" onchange="this.form.submit()"
                        class="w-full sm:w-64 px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500">
                    <option value="">Todas mis sucursales</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" {{ (string) $selectedBranch === (string) $branch->id ? 'selected' : '' }}>
                            {{ $branch->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        @endif

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1.5">Rango rápido</label>
            <div class="flex flex-wrap gap-2">
                @foreach ([
                    'today' => 'Hoy',
                    'yesterday' => 'Ayer',
                    'last7' => 'Últimos 7 días',
                    'thisMonth' => 'Este mes',
                    'lastMonth' => 'Mes anterior',
                    'last3months' => 'Últimos 3 meses',
                ] as $preset => $label)
                    <button type="button" data-preset="{{ $preset }}"
                            class="date-preset px-3 py-1.5 text-sm border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors">
                        {{ $label }}
                    </button>
                @endforeach
            </div>
        </div>

        <div class="flex flex-wrap items-end gap-3">
            <div>
                <label for="start_date" class="block text-sm font-medium text-slate-700 mb-1.5">Desde</label>
                <input type="date" name="start_date" id="start_date" value="{{ $startDate }}"
                       class="px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500">
            </div>
            <div>
                <label for="end_date" class="block text-sm font-medium text-slate-700 mb-1.5">Hasta</label>
                <input type="date" name="end_date" id="end_date" value="{{ $endDate }}"
                       class="px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500">
            </div>
            <button type="submit"
                    class="px-4 py-2 bg-cyan-600 hover:bg-cyan-700 text-white text-sm font-medium rounded-lg transition-colors">
                Filtrar
            </button>
        </div>
    </form>

    {{-- KPIs --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-lg border border-slate-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Número de Ventas</p>
                    <p class="text-2xl font-semibold text-slate-900 mt-1">{{ number_format($totalSalesCount, 0) }}</p>
                </div>
                <div class="w-10 h-10 bg-cyan-50 rounded-lg flex items-center justify-center">
                    <i class="bi bi-receipt text-cyan-600 text-lg"></i>
                </div>
            </div>
            <p class="text-xs text-slate-500 mt-2">Ticket promedio: ${{ number_format($averageTicket, 2) }}</p>
        </div>

        <div class="bg-white rounded-lg border border-slate-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Total Vendido</p>
                    <p class="text-2xl font-semibold text-slate-900 mt-1">${{ number_format($totalRevenue, 2) }}</p>
                </div>
                <div class="w-10 h-10 bg-emerald-50 rounded-lg flex items-center justify-center">
                    <i class="bi bi-currency-dollar text-emerald-600 text-lg"></i>
                </div>
            </div>
            <p class="text-xs text-slate-500 mt-2">En el período seleccionado</p>
        </div>

        <div class="bg-white rounded-lg border border-slate-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Unidades Vendidas</p>
                    <p class="text-2xl font-semibold text-slate-900 mt-1">{{ number_format($unitsSold, 0) }}</p>
                </div>
                <div class="w-10 h-10 bg-sky-50 rounded-lg flex items-center justify-center">
                    <i class="bi bi-box-seam text-sky-600 text-lg"></i>
                </div>
            </div>
            <p class="text-xs text-slate-500 mt-2">Cantidad de productos</p>
        </div>

        <div class="bg-white rounded-lg border border-slate-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Ganancia</p>
                    <p class="text-2xl font-semibold text-slate-900 mt-1">${{ number_format($totalProfit, 2) }}</p>
                </div>
                <div class="w-10 h-10 bg-amber-50 rounded-lg flex items-center justify-center">
                    <i class="bi bi-graph-up-arrow text-amber-600 text-lg"></i>
                </div>
            </div>
            <p class="text-xs text-slate-500 mt-2">Utilidad bruta</p>
        </div>
    </div>

    {{-- Gráfica --}}
    <div class="bg-white rounded-lg border border-slate-200 p-4">
        <div class="flex items-center justify-between mb-4">
            <h2 class="font-semibold text-slate-900">Ventas por día</h2>
            <div class="flex gap-1">
                <button type="button" id="chartTypeBar" class="chart-type-btn px-2.5 py-1 text-xs border border-slate-300 rounded-lg">
                    <i class="bi bi-bar-chart-line"></i>
                </button>
                <button type="button" id="chartTypeLine" class="chart-type-btn px-2.5 py-1 text-xs border border-slate-300 rounded-lg">
                    <i class="bi bi-graph-up"></i>
                </button>
            </div>
        </div>
        @if($totalSalesCount > 0)
            <canvas id="salesChart" height="90"></canvas>
        @else
            <div class="text-center text-slate-500 py-12">
                <i class="bi bi-bar-chart text-4xl text-slate-300 mb-3 block"></i>
                <p>No hay ventas en el período seleccionado</p>
            </div>
        @endif
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Presets rápidos de rango de fechas
    function formatDate(d) {
        return d.toISOString().split('T')[0];
    }

    document.querySelectorAll('.date-preset').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const today = new Date();
            let start = new Date();
            let end = new Date();

            switch (btn.dataset.preset) {
                case 'today':
                    break;
                case 'yesterday':
                    start.setDate(today.getDate() - 1);
                    end.setDate(today.getDate() - 1);
                    break;
                case 'last7':
                    start.setDate(today.getDate() - 6);
                    break;
                case 'thisMonth':
                    start = new Date(today.getFullYear(), today.getMonth(), 1);
                    break;
                case 'lastMonth':
                    start = new Date(today.getFullYear(), today.getMonth() - 1, 1);
                    end = new Date(today.getFullYear(), today.getMonth(), 0);
                    break;
                case 'last3months':
                    start.setMonth(today.getMonth() - 3);
                    break;
            }

            document.getElementById('start_date').value = formatDate(start);
            document.getElementById('end_date').value = formatDate(end);
            document.getElementById('reportFilterForm').submit();
        });
    });

    // Grafica
    const canvas = document.getElementById('salesChart');
    if (canvas) {
        const dates = @json($dates);
        const totals = @json($totals);
        const ctx = canvas.getContext('2d');
        let chart = null;

        function renderChart(type) {
            if (chart) chart.destroy();
            chart = new Chart(ctx, {
                type: type,
                data: {
                    labels: dates,
                    datasets: [{
                        label: 'Ventas ($)',
                        data: totals,
                        backgroundColor: type === 'bar' ? '#0891b2' : 'rgba(8, 145, 178, 0.15)',
                        borderColor: '#0891b2',
                        borderWidth: 2,
                        borderRadius: type === 'bar' ? 6 : 0,
                        fill: type === 'line',
                        tension: 0.3,
                        pointRadius: type === 'line' ? 3 : 0,
                    }],
                },
                options: {
                    responsive: true,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { callback: (v) => '$' + v },
                        },
                    },
                },
            });
        }

        renderChart('bar');

        document.getElementById('chartTypeBar').addEventListener('click', () => renderChart('bar'));
        document.getElementById('chartTypeLine').addEventListener('click', () => renderChart('line'));
    }
});
</script>
@endsection
