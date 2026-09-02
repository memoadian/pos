<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreExpenseRequest;
use App\Models\Expense;
use App\Services\BranchContextService;
use App\Services\ExpenseService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    public function __construct(
        protected BranchContextService $branchContext,
        protected ExpenseService $expenseService,
    ) {
    }

    /**
     * Registrar un gasto contra la caja abierta (AJAX desde Mi Caja).
     */
    public function store(StoreExpenseRequest $request)
    {
        $cashRegister = $request->get('current_cash_register');

        try {
            $expense = $this->expenseService->record(
                $cashRegister,
                $request->input('category'),
                $request->input('description'),
                (float) $request->input('amount'),
                auth()->id(),
            );
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar el gasto: ' . $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Gasto registrado correctamente.',
            'expense' => [
                'id' => $expense->id,
                'category' => $expense->categoryLabel(),
                'description' => $expense->description,
                'amount' => number_format($expense->amount, 2),
            ],
        ]);
    }

    /**
     * Listado de gastos con filtros de fecha, sucursal y categoría.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Expense::class);

        $branches = $this->branchContext->availableBranches();
        $branchIds = $branches->pluck('id');

        [$startDate, $endDate] = $this->resolveDateRange($request);
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();

        $branchId = $request->input('branch');
        if ($branchId && !$branches->contains('id', (int) $branchId)) {
            $branchId = null;
        }

        $base = Expense::query()->whereBetween('created_at', [$start, $end]);
        if ($branchId) {
            $base->where('branch_id', $branchId);
        } else {
            $base->whereIn('branch_id', $branchIds);
        }

        $category = $request->input('category');
        if (array_key_exists($category, Expense::CATEGORIES)) {
            $base->where('category', $category);
        }

        $expenses = (clone $base)
            ->with(['user', 'branch', 'cashRegister'])
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        $periodTotal = (float) (clone $base)->sum('amount');
        $byCategory = (clone $base)
            ->selectRaw('category, SUM(amount) as total')
            ->groupBy('category')
            ->pluck('total', 'category');

        $totalExpenses = Expense::whereIn('branch_id', $branchIds)->count();

        $summary = view('components.table-summary', [
            'paginator' => $expenses,
            'total' => $totalExpenses,
            'singular' => 'gasto',
            'plural' => 'gastos',
            'icon' => 'bi-cash-stack',
        ])->render();

        if ($request->ajax()) {
            return response()->json([
                'rows' => view('expenses.partials.table-rows', compact('expenses'))->render(),
                'pagination' => $expenses->hasPages() ? $expenses->links()->toHtml() : '',
                'summary' => $summary,
                'period_total' => money($periodTotal),
            ]);
        }

        return view('expenses.index', compact(
            'expenses', 'branches', 'branchId', 'startDate', 'endDate',
            'category', 'periodTotal', 'byCategory', 'totalExpenses', 'summary',
        ));
    }

    /**
     * Borrar un gasto (corrección). Admin siempre; el creador solo con su caja abierta.
     */
    public function destroy(Expense $expense)
    {
        $this->authorize('delete', $expense);

        $this->expenseService->delete($expense);

        return back()->with('success', 'Gasto eliminado.');
    }

    /**
     * Rango de fechas del request, "últimos 7 días" por defecto.
     * (Mismo criterio que ReportController / SaleController.)
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
