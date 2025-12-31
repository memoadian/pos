@forelse($branches as $branch)
<tr class="hover:bg-slate-50 transition-colors {{ $branch->trashed() ? 'bg-red-50' : '' }}">
    <td class="px-4 py-3 text-sm text-slate-900">{{ $branch->id }}</td>
    <td class="px-4 py-3">
        <span class="text-sm font-medium text-slate-900">{{ $branch->name }}</span>
    </td>
    <td class="px-4 py-3 text-sm text-slate-600">{{ $branch->address }}</td>
    <td class="px-4 py-3">
        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-700">
            <i class="bi bi-people mr-1"></i>
            {{ $branch->users_count }} usuarios
        </span>
    </td>
    <td class="px-4 py-3">
        <div class="flex flex-col gap-1">
            @if($branch->is_active)
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700">
                    <i class="bi bi-check-circle mr-1"></i>
                    Activa
                </span>
            @else
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-700">
                    <i class="bi bi-x-circle mr-1"></i>
                    Inactiva
                </span>
            @endif

            @if($branch->trashed())
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700">
                    <i class="bi bi-trash mr-1"></i>
                    Eliminada
                </span>
            @endif
        </div>
    </td>
    <td class="px-4 py-3">
        <div class="flex items-center justify-end gap-2">
            @if($branch->trashed())
                @can('restore', $branch)
                    <button onclick="confirmRestore({{ $branch->id }})"
                            class="p-1.5 text-slate-600 hover:text-emerald-600 hover:bg-emerald-50 rounded transition-colors"
                            title="Restaurar sucursal">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </button>
                @endcan
            @else
                @can('update', $branch)
                    <a href="{{ route('branches.edit', $branch) }}"
                       class="p-1.5 text-slate-600 hover:text-cyan-600 hover:bg-cyan-50 rounded transition-colors"
                       title="Editar sucursal">
                        <i class="bi bi-pencil"></i>
                    </a>
                @endcan

                @can('delete', $branch)
                    <button onclick="confirmDelete({{ $branch->id }})"
                            class="p-1.5 text-slate-600 hover:text-red-600 hover:bg-red-50 rounded transition-colors"
                            title="Eliminar sucursal">
                        <i class="bi bi-trash"></i>
                    </button>
                @endcan
            @endif
        </div>
    </td>
</tr>
@empty
<tr>
    <td colspan="6" class="px-4 py-12 text-center text-slate-600">
        <div class="flex flex-col items-center gap-2">
            <i class="bi bi-building text-4xl text-slate-300"></i>
            <p>No se encontraron sucursales</p>
        </div>
    </td>
</tr>
@endforelse
