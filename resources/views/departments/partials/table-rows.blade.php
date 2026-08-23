@forelse($departments as $department)
<tr class="hover:bg-slate-50 transition-colors">
    <td class="px-4 py-3 text-sm text-slate-900">{{ $department->id }}</td>
    <td class="px-4 py-3">
        <span class="text-sm font-medium text-slate-900">{{ $department->name }}</span>
    </td>
    <td class="px-4 py-3">
        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-700">
            <i class="bi bi-box-seam mr-1"></i>
            {{ $department->products_count }} productos
        </span>
    </td>
    <td class="px-4 py-3 text-sm text-slate-600">
        {{ $department->created_at->format('d/m/Y') }}
    </td>
    <td class="px-4 py-3">
        <div class="flex items-center justify-end gap-2">
            @can('update', $department)
                <a href="{{ route('departments.edit', $department) }}"
                   class="p-1.5 text-slate-600 hover:text-cyan-600 hover:bg-cyan-50 rounded transition-colors"
                   title="Editar departamento">
                    <i class="bi bi-pencil"></i>
                </a>
            @endcan

            @can('delete', $department)
                {{-- Un departamento con productos no se puede borrar (lo bloquea el
                     controlador). El boton se ve apagado para que no parezca que
                     el click se pierde. --}}
                @php($blocked = $department->products_count > 0)
                <button onclick="confirmDelete({{ $department->id }})"
                        class="p-1.5 rounded transition-colors text-slate-600 hover:text-red-600 hover:bg-red-50 disabled:text-slate-300 disabled:hover:text-slate-300 disabled:hover:bg-transparent disabled:cursor-not-allowed"
                        title="{{ $blocked ? 'No se puede eliminar: primero mueve o elimina sus '.$department->products_count.' producto(s)' : 'Eliminar departamento' }}"
                        @disabled($blocked)>
                    <i class="bi bi-trash"></i>
                </button>
            @endcan
        </div>
    </td>
</tr>
@empty
<tr>
    <td colspan="5" class="px-4 py-12 text-center text-slate-600">
        <div class="flex flex-col items-center gap-2">
            <i class="bi bi-folder text-4xl text-slate-300"></i>
            <p>No se encontraron departamentos</p>
        </div>
    </td>
</tr>
@endforelse
