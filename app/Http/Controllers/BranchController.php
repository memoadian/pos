<?php

namespace App\Http\Controllers;

use App\Http\Requests\BranchRequest;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BranchController extends Controller
{
    /**
     * Display a listing of branches
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Branch::class);

        $query = Branch::withCount(['users', 'inventories', 'cashRegisters', 'sales']);

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%");
            });
        }

        // Filter by active status
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->input('is_active'));
        }

        // Filter by deleted status
        if ($request->filled('show_deleted')) {
            if ($request->input('show_deleted') === 'only') {
                $query->onlyTrashed();
            } elseif ($request->input('show_deleted') === 'with') {
                $query->withTrashed();
            }
        }

        $branches = $query->orderBy('name')->paginate(15);

        // AJAX support
        if ($request->ajax()) {
            return view('branches.partials.table-rows', compact('branches'));
        }

        return view('branches.index', compact('branches'));
    }

    /**
     * Show the form for creating a new branch
     */
    public function create()
    {
        $this->authorize('create', Branch::class);

        return view('branches.create');
    }

    /**
     * Store a newly created branch in database
     */
    public function store(BranchRequest $request)
    {
        $this->authorize('create', Branch::class);

        DB::beginTransaction();
        try {
            Branch::create([
                'name' => $request->name,
                'address' => $request->address,
                'is_active' => $request->boolean('is_active', true),
            ]);

            DB::commit();

            return redirect()
                ->route('branches.index')
                ->with('success', 'Sucursal creada exitosamente');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->with('error', 'Error al crear la sucursal: ' . $e->getMessage());
        }
    }

    /**
     * Show the form for editing the specified branch
     */
    public function edit(Branch $branch)
    {
        $this->authorize('update', $branch);

        return view('branches.edit', compact('branch'));
    }

    /**
     * Update the specified branch in database
     */
    public function update(BranchRequest $request, Branch $branch)
    {
        $this->authorize('update', $branch);

        DB::beginTransaction();
        try {
            $branch->update([
                'name' => $request->name,
                'address' => $request->address,
                'is_active' => $request->boolean('is_active', $branch->is_active),
            ]);

            DB::commit();

            return redirect()
                ->route('branches.index')
                ->with('success', 'Sucursal actualizada exitosamente');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->with('error', 'Error al actualizar la sucursal: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified branch from database (soft delete)
     */
    public function destroy(Branch $branch)
    {
        $this->authorize('delete', $branch);

        // Verificar si tiene usuarios activos asignados
        if ($branch->users()->where('is_active', true)->count() > 0) {
            return redirect()
                ->route('branches.index')
                ->with('error', 'No se puede eliminar una sucursal con usuarios activos asignados');
        }

        // Verificar si tiene cajas de registro abiertas
        if ($branch->cashRegisters()->where('status', 'abierta')->count() > 0) {
            return redirect()
                ->route('branches.index')
                ->with('error', 'No se puede eliminar una sucursal con cajas abiertas');
        }

        $branch->delete(); // Soft delete

        return redirect()
            ->route('branches.index')
            ->with('success', 'Sucursal eliminada exitosamente');
    }

    /**
     * Restore a soft deleted branch
     */
    public function restore($id)
    {
        $branch = Branch::withTrashed()->findOrFail($id);

        $this->authorize('restore', $branch);

        $branch->restore();

        return redirect()
            ->route('branches.index')
            ->with('success', 'Sucursal restaurada exitosamente');
    }
}
