<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaleTypeRequest;
use App\Models\SaleType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SaleTypeController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', SaleType::class);

        $query = SaleType::withCount(['products', 'productSaleTypes']);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('name', 'like', "%{$search}%");
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->input('is_active'));
        }

        // withQueryString: sin esto los links de paginado pierden los filtros.
        $saleTypes = $query->orderBy('name')->paginate(15)->withQueryString();

        // Total sin filtros: distingue "no hay tipos de venta" de "ninguno coincide".
        $totalSaleTypes = SaleType::count();

        $summary = view('components.table-summary', [
            'paginator' => $saleTypes,
            'total' => $totalSaleTypes,
            'singular' => 'tipo de venta',
            'plural' => 'tipos de venta',
            'icon' => 'bi-rulers',
        ])->render();

        if ($request->ajax()) {
            return response()->json([
                'rows' => view('sale-types.partials.table-rows', compact('saleTypes'))->render(),
                'pagination' => $saleTypes->hasPages() ? $saleTypes->links()->toHtml() : '',
                'summary' => $summary,
            ]);
        }

        return view('sale-types.index', compact('saleTypes', 'totalSaleTypes', 'summary'));
    }

    public function create()
    {
        $this->authorize('create', SaleType::class);
        return view('sale-types.create');
    }

    public function store(SaleTypeRequest $request)
    {
        $this->authorize('create', SaleType::class);

        DB::beginTransaction();
        try {
            SaleType::create([
                'name' => $request->name,
                'base_unit' => $request->base_unit,
                'allows_decimals' => $request->boolean('allows_decimals', false),
                'is_active' => $request->boolean('is_active', true),
            ]);

            DB::commit();

            return redirect()->route('sale-types.index')
                ->with('success', 'Tipo de venta creado exitosamente');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()
                ->with('error', 'Error al crear el tipo de venta: ' . $e->getMessage());
        }
    }

    public function edit(SaleType $saleType)
    {
        $this->authorize('update', $saleType);
        return view('sale-types.edit', compact('saleType'));
    }

    public function update(SaleTypeRequest $request, SaleType $saleType)
    {
        $this->authorize('update', $saleType);

        DB::beginTransaction();
        try {
            $saleType->update([
                'name' => $request->name,
                'base_unit' => $request->base_unit,
                'allows_decimals' => $request->boolean('allows_decimals', $saleType->allows_decimals),
                'is_active' => $request->boolean('is_active', $saleType->is_active),
            ]);

            DB::commit();

            return redirect()->route('sale-types.index')
                ->with('success', 'Tipo de venta actualizado exitosamente');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()
                ->with('error', 'Error al actualizar el tipo de venta: ' . $e->getMessage());
        }
    }

    public function destroy(SaleType $saleType)
    {
        $this->authorize('delete', $saleType);

        // Cuenta tanto los productos que lo tienen como principal como los que
        // lo venden ademas de su tipo principal
        if ($saleType->products()->count() > 0 || $saleType->productSaleTypes()->count() > 0) {
            return redirect()->route('sale-types.index')
                ->with('error', 'No se puede eliminar un tipo de venta con productos asociados');
        }

        $saleType->delete();

        return redirect()->route('sale-types.index')
            ->with('success', 'Tipo de venta eliminado exitosamente');
    }
}
