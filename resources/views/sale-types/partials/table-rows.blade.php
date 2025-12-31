@forelse($saleTypes as $type)
<tr class="hover:bg-slate-50">
    <td class="px-4 py-3 text-sm text-slate-900">{{ $type->id }}</td>
    <td class="px-4 py-3"><span class="text-sm font-medium text-slate-900">{{ $type->name }}</span></td>
    <td class="px-4 py-3 text-sm text-slate-600">{{ $type->code }}</td>
    <td class="px-4 py-3"><span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-700">{{ $type->products_count }} productos</span></td>
    <td class="px-4 py-3">
        @if($type->is_active)<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700"><i class="bi bi-check-circle mr-1"></i>Activo</span>
        @else<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-700"><i class="bi bi-x-circle mr-1"></i>Inactivo</span>@endif
    </td>
    <td class="px-4 py-3"><div class="flex items-center justify-end gap-2">
        @can('update', $type)<a href="{{ route('sale-types.edit', $type) }}" class="p-1.5 text-slate-600 hover:text-cyan-600 hover:bg-cyan-50 rounded transition-colors"><i class="bi bi-pencil"></i></a>@endcan
        @can('delete', $type)<button onclick="confirmDelete({{ $type->id }})" class="p-1.5 text-slate-600 hover:text-red-600 hover:bg-red-50 rounded transition-colors"><i class="bi bi-trash"></i></button>@endcan
    </div></td>
</tr>
@empty
<tr><td colspan="6" class="px-4 py-12 text-center text-slate-600"><div class="flex flex-col items-center gap-2"><i class="bi bi-tag text-4xl text-slate-300"></i><p>No se encontraron tipos de venta</p></div></td></tr>
@endforelse
