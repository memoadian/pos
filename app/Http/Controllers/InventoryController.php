<?php

namespace App\Http\Controllers;

use App\Exports\InventoryExport;
use App\Imports\InventoryImport;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Inventory;
use App\Models\Product;
use App\Services\BranchContextService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class InventoryController extends Controller
{
    public function __construct(protected BranchContextService $branchContext) {}

    /**
     * Display inventory by branch
     */
    public function index(Request $request)
    {
        // Autorización usando Policy
        $this->authorize('viewAny', Inventory::class);

        // Siempre se consulta la sucursal activa del usuario (seleccionada en el header)
        $branchId = $this->branchContext->currentId();
        $query = $this->inventoryQuery($request, $branchId);

        // withQueryString: sin esto los links de paginado pierden los filtros.
        $inventories = $query->orderBy('products.name', 'asc')->paginate(15)->withQueryString();

        // Total sin los filtros del usuario, pero sí de la sucursal activa: la
        // pantalla siempre habla de una sola sucursal.
        $totalInventories = Inventory::when($branchId, fn ($q) => $q->where('branch_id', $branchId))->count();

        $summary = view('components.table-summary', [
            'paginator' => $inventories,
            'total' => $totalInventories,
            'singular' => 'producto',
            'plural' => 'productos',
            'icon' => 'bi-boxes',
        ])->render();

        $departments = Department::orderBy('name')->get();
        $products = Product::where('is_active', true)->orderBy('name')->get();

        // AJAX support: se devuelven las filas, el paginado y el resumen, porque
        // filtrar rehace los tres.
        if ($request->ajax()) {
            return response()->json([
                'rows' => view('inventory.partials.table-rows', compact('inventories'))->render(),
                'pagination' => $inventories->hasPages() ? $inventories->links()->toHtml() : '',
                'summary' => $summary,
            ]);
        }

        return view('inventory.index', compact('inventories', 'departments', 'products', 'totalInventories', 'summary'));
    }

    /**
     * Descarga el inventario en el mismo formato que acepta importStore().
     * Conserva los filtros de la pantalla para exportar solo lo que se ve.
     */
    public function export(Request $request)
    {
        $this->authorize('viewAny', Inventory::class);

        $branch = $this->currentBranchOrAbort();
        $filename = 'inventario-'.(Str::slug($branch->name) ?: 'sucursal').'-'.now()->format('Y-m-d').'.xlsx';

        return Excel::download(
            new InventoryExport($this->inventoryQuery($request, $branch->id)),
            $filename,
        );
    }

    /**
     * Formulario para volver a subir el inventario editado en Excel.
     */
    public function importForm()
    {
        $this->authorize('adjustStock', Inventory::class);

        return view('inventory.import', [
            'branch' => $this->currentBranchOrAbort(),
        ]);
    }

    /**
     * Aplica el stock del archivo a la sucursal activa. Cada cambio queda
     * registrado como movimiento ADJUST para conservar la trazabilidad.
     */
    public function importStore(Request $request)
    {
        $this->authorize('adjustStock', Inventory::class);

        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ], [
            'file.required' => 'Debes seleccionar un archivo',
            'file.mimes' => 'El archivo debe ser un Excel (.xlsx, .xls) o CSV',
            'file.max' => 'El archivo no puede pesar más de 10MB',
        ]);

        $branch = $this->currentBranchOrAbort();
        $import = new InventoryImport($branch);
        Excel::import($import, $request->file('file'));

        if ($import->errorRows > 0) {
            $applied = $import->updated + $import->unchanged;

            $message = $applied === 0
                ? 'No se actualizó ningún producto: las '.$import->errorRows.' fila(s) del archivo tienen errores.'
                : "Se actualizaron {$import->updated} producto(s) y {$import->unchanged} ya estaban correctos. "
                    .$import->errorRows.' fila(s) con errores fueron omitidas.';

            return redirect()
                ->route('inventory.import.create')
                ->with($applied === 0 ? 'error' : 'success', $message)
                ->with('importErrors', $import->errors)
                ->with('importErrorRows', $import->errorRows)
                ->with('importErrorSummary', $import->errorSummary);
        }

        $message = $import->updated === 0
            ? 'Importación completa: el inventario ya coincidía con el archivo.'
            : "Importación completa: {$import->updated} producto(s) actualizado(s), {$import->unchanged} sin cambios.";

        return redirect()
            ->route('inventory.index')
            ->with('success', $message);
    }

    /**
     * Consulta compartida por la pantalla y la descarga para que los filtros
     * se comporten igual en ambos lados.
     */
    private function inventoryQuery(Request $request, ?int $branchId): Builder
    {
        $query = Inventory::with(['product.department', 'branch'])
            ->join('products', 'products.id', '=', 'inventories.product_id')
            ->select('inventories.*');

        if ($branchId) {
            $query->where('inventories.branch_id', $branchId);
        }

        // Filter by department
        if ($request->filled('department')) {
            $query->whereHas('product', function ($q) use ($request) {
                $q->where('department_id', $request->input('department'));
            });
        }

        // Filter by product
        if ($request->filled('product')) {
            $query->where('product_id', $request->input('product'));
        }

        // Filter by in stock: el alta de un producto crea su fila de inventario en
        // cero en cada sucursal, asi que sin este filtro la pantalla es casi toda ceros.
        if ($request->boolean('in_stock')) {
            $query->where('inventories.stock_quantity', '>', 0);
        }

        // Filter by low stock: compara contra el mínimo definido en cada producto
        if ($request->boolean('low_stock')) {
            $query->whereNotNull('products.min_stock')
                ->whereColumn('inventories.stock_quantity', '<=', 'products.min_stock');
        }

        // Search by product name or barcode
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->whereHas('product', fn ($q) => $q->search($search));
        }

        // Filter by first letter of the product name (índice alfabético "# A B C...").
        if ($request->filled('letter')) {
            $letter = $request->input('letter');
            if ($letter === '#') {
                $query->whereRaw("products.name REGEXP '^[^A-Za-z]'");
            } else {
                $query->where('products.name', 'like', $letter.'%');
            }
        }

        return $query;
    }

    private function currentBranchOrAbort(): Branch
    {
        $branch = $this->branchContext->current();

        abort_unless($branch, 403, 'No tienes una sucursal activa para trabajar con inventario.');

        return $branch;
    }
}
