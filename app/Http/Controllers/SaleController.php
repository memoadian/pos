<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Services\BranchContextService;
use App\Services\SaleService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class SaleController extends Controller
{
    public function __construct(
        protected BranchContextService $branchContext,
        protected SaleService $saleService,
    ) {
    }

    /**
     * Listado de ventas con filtros de fecha, sucursal, estado y número.
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

        $query = Sale::with(['user', 'branch', 'client'])
            ->withCount('items')
            ->whereBetween('created_at', [$start, $end]);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        } else {
            $query->whereIn('branch_id', $branchIds);
        }

        if (in_array($request->input('status'), ['completada', 'cancelada'], true)) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('search')) {
            $query->where('id', ltrim($request->input('search'), '#'));
        }

        $sales = $query->orderByDesc('created_at')->paginate(20)->withQueryString();

        $totalSales = Sale::whereIn('branch_id', $branchIds)->count();

        $summary = view('components.table-summary', [
            'paginator' => $sales,
            'total' => $totalSales,
            'singular' => 'venta',
            'plural' => 'ventas',
            'icon' => 'bi-receipt',
        ])->render();

        if ($request->ajax()) {
            return response()->json([
                'rows' => view('sales.partials.table-rows', compact('sales'))->render(),
                'pagination' => $sales->hasPages() ? $sales->links()->toHtml() : '',
                'summary' => $summary,
            ]);
        }

        return view('sales.index', compact('sales', 'branches', 'branchId', 'startDate', 'endDate', 'totalSales', 'summary'));
    }

    /**
     * Detalle de una venta con sus partidas.
     */
    public function show(Sale $sale)
    {
        $this->authorize('view', $sale);

        $sale->load(['items.product', 'items.saleType', 'user', 'branch', 'client', 'cashRegister', 'cancelledBy']);

        return view('sales.show', compact('sale'));
    }

    /**
     * Cancelar una venta: revierte inventario y totales de caja.
     */
    public function cancel(Request $request, Sale $sale)
    {
        $this->authorize('cancel', $sale);

        $validated = $request->validate([
            'reason' => 'nullable|string|max:255',
        ]);

        try {
            $this->saleService->cancelSale($sale, $validated['reason'] ?? null, auth()->id());
        } catch (\Exception $e) {
            return back()->with('error', 'No se pudo cancelar la venta: ' . $e->getMessage());
        }

        return redirect()->route('sales.index')
            ->with('success', "Venta #{$sale->id} cancelada. Inventario y caja actualizados.");
    }

    /**
     * Resuelve el rango de fechas del request con "últimos 7 días" como default.
     * (Mismo criterio que ReportController.)
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
