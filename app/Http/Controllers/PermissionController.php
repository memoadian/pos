<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    /**
     * Display a listing of permissions
     */
    public function index(Request $request)
    {
        $query = Permission::query();

        // Búsqueda
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Filtro por grupo
        if ($request->filled('group')) {
            $query->where('group', $request->input('group'));
        }

        $permissions = $query->with('roles')->orderBy('group')->orderBy('name')->paginate(15);
        $groups = Permission::distinct()->pluck('group')->filter()->sort();

        // Si es petición AJAX, devolver solo las filas
        if ($request->ajax()) {
            return view('permissions.partials.table-rows', compact('permissions'));
        }

        return view('permissions.index', compact('permissions', 'groups'));
    }

    /**
     * Show the form for creating a new permission
     */
    public function create()
    {
        $groups = Permission::distinct()->pluck('group')->filter()->sort();

        return view('permissions.create', compact('groups'));
    }

    /**
     * Store a newly created permission in database
     */
    public function store(Request $request)
    {
        // Validación
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:permissions,name',
            'description' => 'nullable|string|max:500',
            'group' => 'required|string|max:100',
        ], [
            'name.required' => 'El nombre del permiso es obligatorio',
            'name.unique' => 'Ya existe un permiso con este nombre',
            'group.required' => 'El grupo es obligatorio',
        ]);

        // Normalizar nombre (lowercase, trim)
        $validated['name'] = trim(strtolower($validated['name']));

        // Crear permiso
        Permission::create([
            'name' => $validated['name'],
            'guard_name' => 'web',
            'group' => $validated['group'],
            'description' => $validated['description'] ?? null,
        ]);

        return redirect()
            ->route('permissions.index')
            ->with('success', 'Permiso creado exitosamente');
    }

    /**
     * Show the form for editing the specified permission
     */
    public function edit(Permission $permission)
    {
        $groups = Permission::distinct()->pluck('group')->filter()->sort();

        return view('permissions.edit', compact('permission', 'groups'));
    }

    /**
     * Update the specified permission in database
     */
    public function update(Request $request, Permission $permission)
    {
        // Validación
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:permissions,name,' . $permission->id,
            'description' => 'nullable|string|max:500',
            'group' => 'required|string|max:100',
        ], [
            'name.required' => 'El nombre del permiso es obligatorio',
            'name.unique' => 'Ya existe un permiso con este nombre',
            'group.required' => 'El grupo es obligatorio',
        ]);

        // Normalizar nombre
        $validated['name'] = trim(strtolower($validated['name']));

        // Actualizar permiso
        $permission->update([
            'name' => $validated['name'],
            'group' => $validated['group'],
            'description' => $validated['description'] ?? null,
        ]);

        return redirect()
            ->route('permissions.index')
            ->with('success', 'Permiso actualizado exitosamente');
    }

    /**
     * Remove the specified permission from database
     */
    public function destroy(Permission $permission)
    {
        // Validar que no esté asignado a roles
        if ($permission->roles()->count() > 0) {
            return redirect()
                ->route('permissions.index')
                ->with('error', 'No se puede eliminar un permiso asignado a roles');
        }

        $permission->delete();

        return redirect()
            ->route('permissions.index')
            ->with('success', 'Permiso eliminado exitosamente');
    }

    /**
     * Show usage examples for a permission
     */
    public function usageExamples(Permission $permission)
    {
        return view('permissions.usage', compact('permission'));
    }
}
