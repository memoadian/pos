<?php

namespace App\Http\Controllers;

use App\Http\Requests\DepartmentRequest;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DepartmentController extends Controller
{
    /**
     * Display a listing of departments
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Department::class);

        $query = Department::withCount('products');

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('name', 'like', "%{$search}%");
        }

        // withQueryString: sin esto los links de paginado pierden el buscador.
        $departments = $query->orderBy('name')->paginate(15)->withQueryString();

        // Total sin filtros: distingue "no hay departamentos" de "ninguno coincide".
        $totalDepartments = Department::count();

        $summary = view('components.table-summary', [
            'paginator' => $departments,
            'total' => $totalDepartments,
            'singular' => 'departamento',
            'plural' => 'departamentos',
            'icon' => 'bi-tags',
        ])->render();

        // AJAX support: se devuelven las filas, el paginado y el resumen, porque
        // buscar rehace los tres.
        if ($request->ajax()) {
            return response()->json([
                'rows' => view('departments.partials.table-rows', compact('departments'))->render(),
                'pagination' => $departments->hasPages() ? $departments->links()->toHtml() : '',
                'summary' => $summary,
            ]);
        }

        return view('departments.index', compact('departments', 'totalDepartments', 'summary'));
    }

    /**
     * Show the form for creating a new department
     */
    public function create()
    {
        $this->authorize('create', Department::class);

        return view('departments.create');
    }

    /**
     * Store a newly created department in database
     */
    public function store(DepartmentRequest $request)
    {
        $this->authorize('create', Department::class);

        DB::beginTransaction();
        try {
            Department::create([
                'name' => $request->name,
            ]);

            DB::commit();

            return redirect()
                ->route('departments.index')
                ->with('success', 'Departamento creado exitosamente');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->with('error', 'Error al crear el departamento: ' . $e->getMessage());
        }
    }

    /**
     * Show the form for editing the specified department
     */
    public function edit(Department $department)
    {
        $this->authorize('update', $department);

        return view('departments.edit', compact('department'));
    }

    /**
     * Update the specified department in database
     */
    public function update(DepartmentRequest $request, Department $department)
    {
        $this->authorize('update', $department);

        DB::beginTransaction();
        try {
            $department->update([
                'name' => $request->name,
            ]);

            DB::commit();

            return redirect()
                ->route('departments.index')
                ->with('success', 'Departamento actualizado exitosamente');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->with('error', 'Error al actualizar el departamento: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified department from database
     */
    public function destroy(Department $department)
    {
        $this->authorize('delete', $department);

        // No permitir eliminar si tiene productos asociados
        if ($department->products()->count() > 0) {
            return redirect()
                ->route('departments.index')
                ->with('error', 'No se puede eliminar un departamento con productos asociados');
        }

        $department->delete();

        return redirect()
            ->route('departments.index')
            ->with('success', 'Departamento eliminado exitosamente');
    }
}
