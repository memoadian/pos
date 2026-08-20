<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Services\BranchContextService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function __construct(protected BranchContextService $branchContext)
    {
    }

    /**
     * Reporte de ventas: gráfica por día, número de ventas y monto total,
     * filtrable por rango de fechas y sucursal.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Sale::class);

        $branches = $this->branchContext->availableBranches();
        $branchIds = $branches->pluck('id');

        [$startDate, $endDate] = $this->resolveDateRange($request);
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();

        $branchId = $request->input('branch');
        if ($branchId && !$branches->contains('id', (int) $branchId)) {
            $branchId = null;
        }

        $salesQuery = Sale::whereBetween('created_at', [$start, $end]);
        if ($branchId) {
            $salesQuery->where('branch_id', $branchId);
        } else {
            $salesQuery->whereIn('branch_id', $branchIds);
        }

        // Ventas agrupadas por día para la gráfica
        $rows = (clone $salesQuery)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count, SUM(total) as total')
            ->groupBy('date')
            ->get()
            ->keyBy('date');

        $dates = [];
        $counts = [];
        $totals = [];
        $cursor = $start->copy()->startOfDay();
        while ($cursor->lte($end)) {
            $key = $cursor->toDateString();
            $dates[] = $cursor->translatedFormat('d M');
            $counts[] = (int) ($rows[$key]->count ?? 0);
            $totals[] = round((float) ($rows[$key]->total ?? 0), 2);
            $cursor->addDay();
        }

        $totalSalesCount = (int) $rows->sum('count');
        $totalRevenue = (float) $rows->sum('total');
        $totalProfit = (float) (clone $salesQuery)->sum('profit');
        $averageTicket = $totalSalesCount > 0 ? $totalRevenue / $totalSalesCount : 0;

        // En unidad base: sumar la cantidad tal cual mezclaria piezas con cajas
        $unitsSold = (float) SaleItem::whereHas('sale', function ($q) use ($start, $end, $branchId, $branchIds) {
            $q->whereBetween('created_at', [$start, $end]);
            if ($branchId) {
                $q->where('branch_id', $branchId);
            } else {
                $q->whereIn('branch_id', $branchIds);
            }
        })->sum(DB::raw('quantity * conversion_factor'));

        return view('reports.index', [
            'branches' => $branches,
            'selectedBranch' => $branchId,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'dates' => $dates,
            'counts' => $counts,
            'totals' => $totals,
            'totalSalesCount' => $totalSalesCount,
            'totalRevenue' => $totalRevenue,
            'totalProfit' => $totalProfit,
            'unitsSold' => $unitsSold,
            'averageTicket' => $averageTicket,
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

        if (!$startDate || !$endDate) {
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
