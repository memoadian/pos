<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleController extends Controller
{
    /**
     * Display a listing of roles
     */
    public function index()
    {
        $roles = Role::with(['permissions', 'users'])->orderBy('name')->get();

        return view('roles.index', compact('roles'));
    }

    /**
     * Show the form for creating a new role
     */
    public function create()
    {
        $permissions = Permission::orderBy('group')->orderBy('name')->get();

        return view('roles.create', compact('permissions'));
    }

    /**
     * Store a newly created role in database
     */
    public function store(Request $request)
    {
        // Validación
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'description' => 'nullable|string|max:500',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ], [
            'name.required' => 'El nombre del rol es obligatorio',
            'name.unique' => 'Ya existe un rol con este nombre',
            'permissions.*.exists' => 'Uno o más permisos seleccionados no existen',
        ]);

        // Crear rol
        $role = Role::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'guard_name' => 'web',
        ]);

        // Asignar permisos
        if (!empty($validated['permissions'])) {
            $permissions = Permission::whereIn('id', $validated['permissions'])->pluck('name');
            $role->givePermissionTo($permissions);
        }

        return redirect()
            ->route('roles.index')
            ->with('success', 'Rol creado exitosamente');
    }

    /**
     * Display the specified role
     */
    public function show(Role $role)
    {
        $role->load(['permissions', 'users']);

        return view('roles.show', compact('role'));
    }

    /**
     * Show the form for editing the specified role
     */
    public function edit(Role $role)
    {
        $role->load('permissions');
        $permissions = Permission::orderBy('group')->orderBy('name')->get();

        return view('roles.edit', compact('role', 'permissions'));
    }

    /**
     * Update the specified role in database
     */
    public function update(Request $request, Role $role)
    {
        // Validación
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,' . $role->id,
            'description' => 'nullable|string|max:500',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ], [
            'name.required' => 'El nombre del rol es obligatorio',
            'name.unique' => 'Ya existe un rol con este nombre',
            'permissions.*.exists' => 'Uno o más permisos seleccionados no existen',
        ]);

        // Actualizar rol
        $role->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
        ]);

        // Sincronizar permisos
        if (isset($validated['permissions'])) {
            $permissions = Permission::whereIn('id', $validated['permissions'])->pluck('name');
            $role->syncPermissions($permissions);
        } else {
            $role->syncPermissions([]);
        }

        return redirect()
            ->route('roles.index')
            ->with('success', 'Rol actualizado exitosamente');
    }

    /**
     * Remove the specified role from database
     */
    public function destroy(Role $role)
    {
        // Validar que no sea un rol del sistema
        if (in_array($role->name, ['Admin', 'Admin'])) {
            return redirect()
                ->route('roles.index')
                ->with('error', 'No se pueden eliminar los roles del sistema');
        }

        // Validar que no tenga usuarios asignados
        if ($role->users()->count() > 0) {
            return redirect()
                ->route('roles.index')
                ->with('error', 'No se puede eliminar un rol con usuarios asignados');
        }

        $role->delete();

        return redirect()
            ->route('roles.index')
            ->with('success', 'Rol eliminado exitosamente');
    }
}
