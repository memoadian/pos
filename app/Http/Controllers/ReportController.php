<?php

namespace App\Http\Controllers;

use App\Models\CashRegister;
use App\Models\Expense;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Services\BranchContextService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function __construct(protected BranchContextService $branchContext) {}

    /**
     * Reporte de ventas filtrable por rango de fechas y sucursal.
     *
     * El modo "cash_register" (predeterminado) cuenta cada venta en el día de
     * la caja a la que pertenece, no en el día en que se tecleó. Así una caja
     * abierta con fecha de ayer y usada hoy se reporta como parte de ayer.
     * El modo "sale" conserva el comportamiento anterior (fecha de captura).
     */
    public function index(Request $request)
    {
        $this->authorize('ver reportes');

        $branches = $this->branchContext->availableBranches();
        $branchIds = $branches->pluck('id');

        [$startDate, $endDate] = $this->resolveDateRange($request);
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();

        $branchId = $request->input('branch');
        if ($branchId && ! $branches->contains('id', (int) $branchId)) {
            $branchId = null;
        }

        $groupBy = in_array($request->input('group_by'), ['cash_register', 'sale'], true)
            ? $request->input('group_by')
            : 'cash_register';
        $groupByCashRegister = $groupBy === 'cash_register';

        $salesQuery = Sale::query()->completed();

        if ($groupByCashRegister) {
            $salesQuery
                ->join('cash_registers', 'cash_registers.id', '=', 'sales.cash_register_id')
                ->whereBetween('cash_registers.opened_at', [$start, $end]);
            $salesDateExpression = 'DATE(cash_registers.opened_at)';
        } else {
            $salesQuery->whereBetween('sales.created_at', [$start, $end]);
            $salesDateExpression = 'DATE(sales.created_at)';
        }

        if ($branchId) {
            $salesQuery->where('sales.branch_id', $branchId);
        } else {
            $salesQuery->whereIn('sales.branch_id', $branchIds);
        }

        // Ventas agrupadas por día. En modo caja, "date" es la fecha de
        // apertura de la caja; en modo venta, la fecha de captura de la venta.
        $rows = (clone $salesQuery)
            ->selectRaw(
                "{$salesDateExpression} as date,
                COUNT(*) as count,
                COUNT(DISTINCT sales.cash_register_id) as registers,
                SUM(sales.total) as total,
                SUM(sales.profit) as profit"
            )
            ->groupBy('date')
            ->get()
            ->keyBy('date');

        $dates = [];
        $counts = [];
        $totals = [];
        $periodKeys = [];
        $cursor = $start->copy()->startOfDay();
        while ($cursor->lte($end)) {
            $key = $cursor->toDateString();
            $periodKeys[] = $key;
            $dates[] = $cursor->copy()->locale('es')->translatedFormat('d M');
            $counts[] = (int) ($rows[$key]->count ?? 0);
            $totals[] = round((float) ($rows[$key]->total ?? 0), 2);
            $cursor->addDay();
        }

        $totalSalesCount = (int) $rows->sum('count');
        $totalRevenue = (float) $rows->sum('total');
        $totalProfit = (float) (clone $salesQuery)->sum('sales.profit');
        $averageTicket = $totalSalesCount > 0 ? $totalRevenue / $totalSalesCount : 0;

        // Totales por método de pago del período (se muestran en el resumen
        // diario y sirven para conciliar contra el corte de caja).
        $paymentTotals = (clone $salesQuery)
            ->selectRaw(
                "SUM(CASE WHEN sales.payment_method = 'efectivo' THEN sales.total ELSE 0 END) as cash,
                SUM(CASE WHEN sales.payment_method = 'tarjeta' THEN sales.total ELSE 0 END) as card,
                SUM(CASE WHEN sales.payment_method = 'transferencia' THEN sales.total ELSE 0 END) as transfer"
            )
            ->first();

        // En unidad base: sumar la cantidad tal cual mezclaria piezas con cajas.
        $unitsQuery = SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sales.status', 'completada');

        if ($groupByCashRegister) {
            $unitsQuery
                ->join('cash_registers', 'cash_registers.id', '=', 'sales.cash_register_id')
                ->whereBetween('cash_registers.opened_at', [$start, $end]);
        } else {
            $unitsQuery->whereBetween('sales.created_at', [$start, $end]);
        }

        if ($branchId) {
            $unitsQuery->where('sales.branch_id', $branchId);
        } else {
            $unitsQuery->whereIn('sales.branch_id', $branchIds);
        }

        $unitsSold = (float) $unitsQuery->sum(DB::raw('sale_items.quantity * sale_items.conversion_factor'));

        // Gastos del período (bajan la utilidad neta). En modo caja se toman
        // contra la fecha de la caja, igual que las ventas.
        $expensesQuery = Expense::query();

        if ($groupByCashRegister) {
            $expensesQuery
                ->join('cash_registers', 'cash_registers.id', '=', 'expenses.cash_register_id')
                ->whereBetween('cash_registers.opened_at', [$start, $end]);
            $expenseDateExpression = 'DATE(cash_registers.opened_at)';
        } else {
            $expensesQuery->whereBetween('expenses.created_at', [$start, $end]);
            $expenseDateExpression = 'DATE(expenses.created_at)';
        }

        if ($branchId) {
            $expensesQuery->where('expenses.branch_id', $branchId);
        } else {
            $expensesQuery->whereIn('expenses.branch_id', $branchIds);
        }

        $totalExpenses = (float) (clone $expensesQuery)->sum('expenses.amount');
        $expensesByCategory = (clone $expensesQuery)
            ->selectRaw('expenses.category, SUM(expenses.amount) as total')
            ->groupBy('expenses.category')
            ->pluck('total', 'category');
        $expensesByDate = (clone $expensesQuery)
            ->selectRaw("{$expenseDateExpression} as date, SUM(expenses.amount) as total")
            ->groupBy('date')
            ->pluck('total', 'date');

        // Cajas registradas en el período (incluye las que no tuvieron ventas).
        $registerCounts = collect();
        if ($groupByCashRegister) {
            $registerCounts = CashRegister::query()
                ->whereBetween('opened_at', [$start, $end])
                ->when(
                    $branchId,
                    fn ($q) => $q->where('branch_id', $branchId),
                    fn ($q) => $q->whereIn('branch_id', $branchIds),
                )
                ->selectRaw('DATE(opened_at) as date, COUNT(*) as count')
                ->groupBy('date')
                ->pluck('count', 'date');
        }

        // Resumen diario: una fila por fecha con cajas, ventas, gastos y neto.
        $dailySummary = collect($periodKeys)
            ->map(function (string $key) use ($rows, $registerCounts, $expensesByDate, $groupByCashRegister) {
                $row = $rows[$key] ?? null;

                return [
                    'date' => $key,
                    'label' => Carbon::parse($key)->locale('es')->translatedFormat('d M Y'),
                    'registers' => $groupByCashRegister
                        ? (int) ($registerCounts[$key] ?? 0)
                        : (int) ($row->registers ?? 0),
                    'count' => (int) ($row->count ?? 0),
                    'total' => (float) ($row->total ?? 0),
                    'profit' => (float) ($row->profit ?? 0),
                    'expenses' => (float) ($expensesByDate[$key] ?? 0),
                ];
            })
            ->map(fn (array $row) => [
                ...$row,
                'net_profit' => $row['profit'] - $row['expenses'],
            ])
            ->filter(fn (array $row) => $row['registers'] > 0 || $row['count'] > 0 || $row['expenses'] != 0)
            ->values();

        $registers = null;
        if ($groupByCashRegister) {
            $registers = CashRegister::query()
                ->with(['user', 'branch'])
                ->withCount(['sales as completed_sales_count' => fn ($q) => $q->where('status', 'completada')])
                ->withSum('expenses as expenses_total', 'amount')
                ->whereBetween('opened_at', [$start, $end])
                ->when(
                    $branchId,
                    fn ($q) => $q->where('branch_id', $branchId),
                    fn ($q) => $q->whereIn('branch_id', $branchIds),
                )
                ->orderByDesc('opened_at')
                ->paginate(20, ['*'], 'register_page')
                ->withQueryString();
        }

        $netProfit = $totalProfit - $totalExpenses;

        return view('reports.index', [
            'branches' => $branches,
            'selectedBranch' => $branchId,
            'groupBy' => $groupBy,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'dates' => $dates,
            'counts' => $counts,
            'totals' => $totals,
            'totalSalesCount' => $totalSalesCount,
            'totalRevenue' => $totalRevenue,
            'totalProfit' => $totalProfit,
            'totalExpenses' => $totalExpenses,
            'netProfit' => $netProfit,
            'expensesByCategory' => $expensesByCategory,
            'unitsSold' => $unitsSold,
            'averageTicket' => $averageTicket,
            'paymentTotals' => [
                'cash' => (float) ($paymentTotals->cash ?? 0),
                'card' => (float) ($paymentTotals->card ?? 0),
                'transfer' => (float) ($paymentTotals->transfer ?? 0),
            ],
            'dailySummary' => $dailySummary,
            'registers' => $registers,
            'totalRegisters' => (int) $registerCounts->sum(),
        ]);
    }

    /**
     * Resuelve el rango de fechas del request, validando límites,
     * con "últimos 7 días" como default.
     */
    private function resolveDateRange(Request $request): array
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        if (! $startDate || ! $endDate) {
            return [
                Carbon::today()->subDays(6)->toDateString(),
                Carbon::today()->toDateString(),
            ];
        }

        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        if ($start->gt($end)) {
            [$start, $end] = [$end, $start];
        }

        if ($start->diffInDays($end) > 365) {
            $start = $end->copy()->subDays(365);
        }

        return [$start->toDateString(), $end->toDateString()];
    }
}
