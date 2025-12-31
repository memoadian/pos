@forelse($users as $user)
<tr class="hover:bg-slate-50 transition-colors">
    <td class="px-4 py-3 text-sm text-slate-900">{{ $user->id }}</td>
    <td class="px-4 py-3">
        <span class="text-sm font-medium text-slate-900">{{ $user->name }}</span>
    </td>
    <td class="px-4 py-3 text-sm text-slate-600">{{ $user->username }}</td>
    <td class="px-4 py-3 text-sm text-slate-600">{{ $user->email }}</td>
    <td class="px-4 py-3">
        @if($user->branch)
            <span class="text-sm text-slate-600">{{ $user->branch->name }}</span>
        @else
            <span class="text-sm text-slate-400 italic">Sin sucursal</span>
        @endif
    </td>
    <td class="px-4 py-3">
        <div class="flex flex-wrap gap-1">
            @forelse($user->roles as $role)
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-700">
                    {{ $role->name }}
                </span>
            @empty
                <span class="text-xs text-slate-400 italic">Sin roles</span>
            @endforelse
        </div>
    </td>
    <td class="px-4 py-3">
        @if($user->is_active)
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700">
                <i class="bi bi-check-circle mr-1"></i>
                Activo
            </span>
        @else
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-700">
                <i class="bi bi-x-circle mr-1"></i>
                Inactivo
            </span>
        @endif

        {{-- Descomentar cuando ejecutes las migraciones y agregues SoftDeletes al modelo User
        @if($user->trashed())
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700 ml-1">
                <i class="bi bi-trash mr-1"></i>
                Eliminado
            </span>
        @endif
        --}}
    </td>
    <td class="px-4 py-3">
        <div class="flex items-center justify-end gap-2">
            {{-- Impersonate button --}}
            @if(auth()->user()->canImpersonate() && $user->canBeImpersonated() && auth()->id() !== $user->id)
                <a href="{{ route('impersonate', $user->id) }}"
                   class="p-1.5 text-slate-600 hover:text-amber-600 hover:bg-amber-50 rounded transition-colors"
                   title="Impersonar usuario">
                    <i class="bi bi-person-badge"></i>
                </a>
            @endif

            @can('update', $user)
                <a href="{{ route('users.edit', $user) }}"
                   class="p-1.5 text-slate-600 hover:text-cyan-600 hover:bg-cyan-50 rounded transition-colors"
                   title="Editar usuario">
                    <i class="bi bi-pencil"></i>
                </a>
            @endcan

            @can('delete', $user)
                @if(auth()->id() !== $user->id)
                    <button onclick="confirmDelete({{ $user->id }})"
                            class="p-1.5 text-slate-600 hover:text-red-600 hover:bg-red-50 rounded transition-colors"
                            title="Eliminar usuario">
                        <i class="bi bi-trash"></i>
                    </button>
                @endif
            @endcan
        </div>
    </td>
</tr>
@empty
<tr>
    <td colspan="8" class="px-4 py-12 text-center text-slate-600">
        <div class="flex flex-col items-center gap-2">
            <i class="bi bi-people text-4xl text-slate-300"></i>
            <p>No se encontraron usuarios</p>
        </div>
    </td>
</tr>
@endforelse
