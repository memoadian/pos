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

        $query = SaleType::withCount('products');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('name', 'like', "%{$search}%");
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->input('is_active'));
        }

        $saleTypes = $query->orderBy('name')->paginate(15);

        if ($request->ajax()) {
            return view('sale-types.partials.table-rows', compact('saleTypes'));
        }

        return view('sale-types.index', compact('saleTypes'));
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

        if ($saleType->products()->count() > 0) {
            return redirect()->route('sale-types.index')
                ->with('error', 'No se puede eliminar un tipo de venta con productos asociados');
        }

        $saleType->delete();

        return redirect()->route('sale-types.index')
            ->with('success', 'Tipo de venta eliminado exitosamente');
    }
}
