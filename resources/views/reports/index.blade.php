@extends('layouts.app')
@section('title', 'Reportes')
@section('content')
<div class="space-y-6">
    @include('components.alerts')

    <div>
        <h1 class="text-xl font-semibold text-slate-900">Reportes</h1>
        <p class="text-sm text-slate-500 mt-1">
            @if($groupBy === 'cash_register')
                Ventas contadas por la fecha de apertura de la caja
            @else
                Ventas contadas por la fecha en que se registró la venta
            @endif
        </p>
    </div>

    {{-- Filtros --}}
    <form method="GET" action="{{ route('reports.index') }}" id="reportFilterForm" class="bg-white rounded-lg border border-slate-200 p-4 space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
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
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Contar ventas por</label>
                <select name="group_by" onchange="this.form.submit()"
                        class="w-full sm:w-72 px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500">
                    <option value="cash_register" @selected($groupBy === 'cash_register')>Caja diaria (fecha de apertura)</option>
                    <option value="sale" @selected($groupBy === 'sale')>Fecha de la venta</option>
                </select>
            </div>
        </div>

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

    @if($groupBy === 'cash_register')
        <div class="bg-cyan-50 border border-cyan-200 rounded-lg p-4 flex gap-3">
            <i class="bi bi-calendar3 text-cyan-600 mt-0.5"></i>
            <div class="text-sm text-cyan-800">
                <p class="font-medium">Reporte por caja diaria</p>
                <p class="mt-1">
                    Cada venta se cuenta en la fecha de apertura de su caja. Una caja abierta con fecha anterior
                    reporta sus ventas en esa fecha, aunque se hayan capturado después.
                    En el período hay <strong>{{ number_format($totalRegisters, 0) }}</strong> caja(s).
                </p>
            </div>
        </div>
    @endif

    {{-- KPIs --}}
    <div class="grid grid-cols-2 lg:grid-cols-3 gap-4">
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
            <p class="text-xs text-slate-500 mt-2">Ticket promedio: {{ money($averageTicket) }}</p>
        </div>

        <div class="bg-white rounded-lg border border-slate-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Total Vendido</p>
                    <p class="text-2xl font-semibold text-slate-900 mt-1">{{ money($totalRevenue) }}</p>
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
                    <p class="text-2xl font-semibold text-slate-900 mt-1">{{ money($totalProfit) }}</p>
                </div>
                <div class="w-10 h-10 bg-amber-50 rounded-lg flex items-center justify-center">
                    <i class="bi bi-graph-up-arrow text-amber-600 text-lg"></i>
                </div>
            </div>
            <p class="text-xs text-slate-500 mt-2">Utilidad bruta</p>
        </div>

        <div class="bg-white rounded-lg border border-slate-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Gastos</p>
                    <p class="text-2xl font-semibold text-slate-900 mt-1">{{ money($totalExpenses) }}</p>
                </div>
                <div class="w-10 h-10 bg-red-50 rounded-lg flex items-center justify-center">
                    <i class="bi bi-cash-stack text-red-600 text-lg"></i>
                </div>
            </div>
            <p class="text-xs text-slate-500 mt-2">En el período seleccionado</p>
        </div>

        <div class="bg-white rounded-lg border border-slate-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Utilidad Neta</p>
                    <p class="text-2xl font-semibold mt-1 {{ $netProfit >= 0 ? 'text-emerald-600' : 'text-red-600' }}">{{ money($netProfit) }}</p>
                </div>
                <div class="w-10 h-10 bg-emerald-50 rounded-lg flex items-center justify-center">
                    <i class="bi bi-wallet2 text-emerald-600 text-lg"></i>
                </div>
            </div>
            <p class="text-xs text-slate-500 mt-2">Utilidad bruta − gastos</p>
        </div>
    </div>

    {{-- Cobros del período, para conciliar contra los cortes de caja --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white rounded-lg border border-slate-200 p-4 flex items-center justify-between">
            <div>
                <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Efectivo</p>
                <p class="text-lg font-semibold text-slate-900 mt-1">{{ money($paymentTotals['cash']) }}</p>
            </div>
            <i class="bi bi-cash text-emerald-600 text-xl"></i>
        </div>
        <div class="bg-white rounded-lg border border-slate-200 p-4 flex items-center justify-between">
            <div>
                <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Tarjeta</p>
                <p class="text-lg font-semibold text-slate-900 mt-1">{{ money($paymentTotals['card']) }}</p>
            </div>
            <i class="bi bi-credit-card text-sky-600 text-xl"></i>
        </div>
        <div class="bg-white rounded-lg border border-slate-200 p-4 flex items-center justify-between">
            <div>
                <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Transferencia</p>
                <p class="text-lg font-semibold text-slate-900 mt-1">{{ money($paymentTotals['transfer']) }}</p>
            </div>
            <i class="bi bi-arrow-left-right text-violet-600 text-xl"></i>
        </div>
    </div>

    @if($expensesByCategory->isNotEmpty())
    {{-- Gastos por categoría --}}
    <div class="bg-white rounded-lg border border-slate-200 p-4">
        <h2 class="font-semibold text-slate-900 mb-3">Gastos por categoría</h2>
        <div class="space-y-2">
            @foreach($expensesByCategory as $cat => $total)
            <div class="flex items-center justify-between text-sm">
                <span class="text-slate-600">{{ \App\Models\Expense::CATEGORIES[$cat] ?? $cat }}</span>
                <span class="font-medium text-slate-900 tabular-nums">{{ money($total) }}</span>
            </div>
            @endforeach
            <div class="flex items-center justify-between text-sm border-t border-slate-200 pt-2 font-semibold">
                <span class="text-slate-700">Total</span>
                <span class="text-slate-900 tabular-nums">{{ money($totalExpenses) }}</span>
            </div>
        </div>
    </div>
    @endif

    {{-- Resumen de ventas diarias --}}
    <div class="bg-white rounded-lg border border-slate-200 overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-200 flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-slate-900">Ventas diarias</h2>
                <p class="text-xs text-slate-500 mt-0.5">
                    @if($groupBy === 'cash_register')
                        Agrupadas por la fecha de apertura de las cajas
                    @else
                        Agrupadas por la fecha de captura de las ventas
                    @endif
                </p>
            </div>
            <span class="text-xs text-slate-500">{{ $startDate }} — {{ $endDate }}</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-3 text-left text-sm font-medium text-slate-700">Fecha</th>
                        <th class="px-4 py-3 text-right text-sm font-medium text-slate-700">Cajas</th>
                        <th class="px-4 py-3 text-right text-sm font-medium text-slate-700">Ventas</th>
                        <th class="px-4 py-3 text-right text-sm font-medium text-slate-700">Total vendido</th>
                        <th class="px-4 py-3 text-right text-sm font-medium text-slate-700">Gastos</th>
                        <th class="px-4 py-3 text-right text-sm font-medium text-slate-700">Utilidad neta</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($dailySummary as $day)
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3 whitespace-nowrap">
                            <span class="text-sm font-medium text-slate-900">{{ $day['label'] }}</span>
                            <p class="text-xs text-slate-500">{{ \Carbon\Carbon::parse($day['date'])->locale('es')->translatedFormat('l') }}</p>
                        </td>
                        <td class="px-4 py-3 text-right text-sm tabular-nums">{{ number_format($day['registers'], 0) }}</td>
                        <td class="px-4 py-3 text-right text-sm tabular-nums">{{ number_format($day['count'], 0) }}</td>
                        <td class="px-4 py-3 text-right text-sm font-medium tabular-nums text-cyan-700">{{ money($day['total']) }}</td>
                        <td class="px-4 py-3 text-right text-sm tabular-nums text-red-600">{{ money($day['expenses']) }}</td>
                        <td class="px-4 py-3 text-right text-sm font-medium tabular-nums {{ $day['net_profit'] >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                            {{ money($day['net_profit']) }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-sm text-slate-500">
                            No hay cajas, ventas ni gastos en el período seleccionado.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Gráfica --}}
    <div class="bg-white rounded-lg border border-slate-200 p-4">
        <div class="flex items-center justify-between mb-4">
            <h2 class="font-semibold text-slate-900">
                Ventas por día {{ $groupBy === 'cash_register' ? '(fecha de caja)' : '(fecha de venta)' }}
            </h2>
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

    @if($groupBy === 'cash_register' && $registers)
    {{-- Detalle por caja: permite conciliar cada corte contra su fecha --}}
    <div class="bg-white rounded-lg border border-slate-200 overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-200">
            <h2 class="font-semibold text-slate-900">Cajas del período</h2>
            <p class="text-xs text-slate-500 mt-0.5">Cada caja conserva la fecha con la que fue abierta.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-3 text-left text-sm font-medium text-slate-700">Fecha</th>
                        <th class="px-4 py-3 text-left text-sm font-medium text-slate-700">Caja / Usuario</th>
                        <th class="px-4 py-3 text-left text-sm font-medium text-slate-700">Sucursal</th>
                        <th class="px-4 py-3 text-right text-sm font-medium text-slate-700">Ventas</th>
                        <th class="px-4 py-3 text-right text-sm font-medium text-slate-700">Total</th>
                        <th class="px-4 py-3 text-right text-sm font-medium text-slate-700">Efectivo</th>
                        <th class="px-4 py-3 text-right text-sm font-medium text-slate-700">Tarjeta</th>
                        <th class="px-4 py-3 text-right text-sm font-medium text-slate-700">Transferencia</th>
                        <th class="px-4 py-3 text-right text-sm font-medium text-slate-700">Ganancia</th>
                        <th class="px-4 py-3 text-right text-sm font-medium text-slate-700">Gastos</th>
                        <th class="px-4 py-3 text-center text-sm font-medium text-slate-700">Estado</th>
                        <th class="px-4 py-3 text-right text-sm font-medium text-slate-700"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($registers as $register)
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3 whitespace-nowrap">
                            <span class="text-sm font-medium text-slate-900">{{ $register->opened_at->format('d/m/Y') }}</span>
                            <p class="text-xs text-slate-500">Apertura {{ $register->opened_at->format('H:i') }}</p>
                            @if($register->closed_at)
                                <p class="text-xs text-slate-500">Cierre {{ $register->closed_at->format('d/m/Y H:i') }}</p>
                            @endif
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <span class="text-sm font-medium text-slate-900">#{{ $register->id }} · {{ $register->user->name }}</span>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <span class="text-sm text-slate-600">{{ $register->branch->name }}</span>
                        </td>
                        <td class="px-4 py-3 text-right text-sm tabular-nums">{{ number_format($register->completed_sales_count, 0) }}</td>
                        <td class="px-4 py-3 text-right text-sm font-medium tabular-nums text-cyan-700">{{ money($register->total_sales) }}</td>
                        <td class="px-4 py-3 text-right text-sm tabular-nums">{{ money($register->cash_sales) }}</td>
                        <td class="px-4 py-3 text-right text-sm tabular-nums">{{ money($register->card_sales) }}</td>
                        <td class="px-4 py-3 text-right text-sm tabular-nums">{{ money($register->transfer_sales) }}</td>
                        <td class="px-4 py-3 text-right text-sm tabular-nums text-emerald-600">{{ money($register->total_profit) }}</td>
                        <td class="px-4 py-3 text-right text-sm tabular-nums text-red-600">{{ money($register->expenses_total ?? 0) }}</td>
                        <td class="px-4 py-3 text-center whitespace-nowrap">
                            @if($register->status === 'abierta')
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800">
                                    <i class="bi bi-unlock-fill mr-1"></i> Abierta
                                </span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-700">
                                    <i class="bi bi-lock-fill mr-1"></i> Cerrada
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            <a href="{{ route('cash-register.show', $register) }}"
                               class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs bg-cyan-100 hover:bg-cyan-200 text-cyan-700 font-medium rounded transition-colors">
                                <i class="bi bi-eye"></i> Ver
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="12" class="px-4 py-8 text-center text-sm text-slate-500">
                            No hay cajas registradas en el período seleccionado.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($registers->hasPages())
        <div class="px-4 py-3 border-t border-slate-200">
            {{ $registers->links() }}
        </div>
        @endif
    </div>
    @endif
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
