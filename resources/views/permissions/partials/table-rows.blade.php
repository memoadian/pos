@forelse($permissions as $permission)
<tr class="hover:bg-slate-50 transition-colors">
    <td class="px-4 py-3 text-sm text-slate-600">{{ $permission->id }}</td>
    <td class="px-4 py-3">
        <span class="text-sm font-medium text-slate-900">{{ $permission->name }}</span>
    </td>
    <td class="px-4 py-3">
        <span class="text-sm text-slate-600">{{ $permission->description ?? '-' }}</span>
    </td>
    <td class="px-4 py-3">
        @php
            $groupColors = [
                'Envios' => 'bg-sky-100 text-sky-700',
                'Pagos' => 'bg-violet-100 text-violet-700',
            ];
            $colorClass = $groupColors[$permission->group] ?? 'bg-slate-100 text-slate-700';
        @endphp
        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $colorClass }}">
            {{ $permission->group }}
        </span>
    </td>
    <td class="px-4 py-3">
        <button onclick="showUsage({{ $permission->id }})"
                class="inline-flex items-center gap-1 px-2.5 py-0.5 bg-emerald-100 text-emerald-700 rounded-full text-xs font-medium hover:bg-emerald-200 transition-colors">
            <i class="bi bi-code-square"></i>
            <span>Ver ejemplos</span>
        </button>
    </td>
    <td class="px-4 py-3">
        <div class="flex items-center justify-end gap-2">
            <a href="{{ route('permissions.edit', $permission) }}"
               class="p-1.5 text-slate-600 hover:text-emerald-600 hover:bg-emerald-50 rounded transition-colors">
                <i class="bi bi-pencil"></i>
            </a>
            @if($permission->roles->count() === 0)
            <button onclick="confirmDelete({{ $permission->id }})"
                    class="p-1.5 text-slate-600 hover:text-red-600 hover:bg-red-50 rounded transition-colors">
                <i class="bi bi-trash"></i>
            </button>
            @endif
        </div>
    </td>
</tr>
@empty
<tr>
    <td colspan="6" class="px-4 py-12 text-center text-slate-600">
        No se encontraron permisos
    </td>
</tr>
@endforelse
