<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    /**
     * Display a listing of users
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', User::class);

        $query = User::with(['branch', 'roles']);

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%");
            });
        }

        // Filter by branch
        if ($request->filled('branch')) {
            $query->where('branch_id', $request->input('branch'));
        }

        // Filter by role (Spatie)
        if ($request->filled('role')) {
            $query->whereHas('roles', function($q) use ($request) {
                $q->where('id', $request->input('role'));
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

        $users = $query->orderBy('name')->paginate(15);

        // Get filter options
        $branches = Branch::where('is_active', true)->orderBy('name')->get();
        $roles = Role::orderBy('name')->get();

        // AJAX support
        if ($request->ajax()) {
            return view('users.partials.table-rows', compact('users'));
        }

        return view('users.index', compact('users', 'branches', 'roles'));
    }

    /**
     * Show the form for creating a new user
     */
    public function create()
    {
        $this->authorize('create', User::class);

        $branches = Branch::where('is_active', true)->orderBy('name')->get();
        $roles = Role::orderBy('name')->get();

        return view('users.create', compact('branches', 'roles'));
    }

    /**
     * Store a newly created user in database
     */
    public function store(UserRequest $request)
    {
        $this->authorize('create', User::class);

        DB::beginTransaction();
        try {
            // Create user
            $user = User::create([
                'name' => $request->name,
                'username' => $request->username,
                'email' => $request->email,
                'password' => $request->password, // Auto-hashed by cast
                'branch_id' => $request->branch_id,
                'is_active' => $request->boolean('is_active', true),
            ]);

            // Assign roles
            $roles = Role::whereIn('id', $request->roles)->pluck('name');
            $user->syncRoles($roles);

            DB::commit();

            return redirect()
                ->route('users.index')
                ->with('success', 'Usuario creado exitosamente');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->with('error', 'Error al crear el usuario: ' . $e->getMessage());
        }
    }

    /**
     * Show the form for editing the specified user
     */
    public function edit(User $user)
    {
        $this->authorize('update', $user);

        $user->load('roles');
        $branches = Branch::where('is_active', true)->orderBy('name')->get();
        $roles = Role::orderBy('name')->get();

        return view('users.edit', compact('user', 'branches', 'roles'));
    }

    /**
     * Update the specified user in database
     */
    public function update(UserRequest $request, User $user)
    {
        $this->authorize('update', $user);

        // Cannot deactivate yourself
        if (auth()->id() === $user->id && $request->boolean('is_active') === false) {
            return back()
                ->withInput()
                ->with('error', 'No puedes desactivar tu propia cuenta');
        }

        DB::beginTransaction();
        try {
            // Prepare data
            $data = [
                'name' => $request->name,
                'username' => $request->username,
                'email' => $request->email,
                'branch_id' => $request->branch_id,
                'is_active' => $request->boolean('is_active', true),
            ];

            // Only update password if provided
            if ($request->filled('password')) {
                $data['password'] = $request->password;
            }

            // Update user
            $user->update($data);

            // Sync roles
            $roles = Role::whereIn('id', $request->roles)->pluck('name');
            $user->syncRoles($roles);

            DB::commit();

            return redirect()
                ->route('users.index')
                ->with('success', 'Usuario actualizado exitosamente');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->with('error', 'Error al actualizar el usuario: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified user from database (soft delete)
     */
    public function destroy(User $user)
    {
        $this->authorize('delete', $user);

        // Additional check: cannot delete yourself
        if (auth()->id() === $user->id) {
            return redirect()
                ->route('users.index')
                ->with('error', 'No puedes eliminar tu propia cuenta');
        }

        // Check if user has active cash registers
        if ($user->cashRegisters()->where('status', 'abierta')->count() > 0) {
            return redirect()
                ->route('users.index')
                ->with('error', 'No se puede eliminar un usuario con cajas abiertas');
        }

        $user->delete(); // Soft delete

        return redirect()
            ->route('users.index')
            ->with('success', 'Usuario eliminado exitosamente');
    }
}
