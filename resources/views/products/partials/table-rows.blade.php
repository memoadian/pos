@forelse($products as $product)
<tr class="hover:bg-slate-50 transition-colors">
    <td class="px-4 py-3 text-sm text-slate-900">{{ $product->id }}</td>
    <td class="px-4 py-3 text-sm text-slate-600">{{ $product->barcode ?? 'N/A' }}</td>
    <td class="px-4 py-3">
        <span class="text-sm font-medium text-slate-900">{{ $product->name }}</span>
        @if($product->aliases->isNotEmpty())
        <div class="flex flex-wrap items-center gap-1 mt-1">
            @foreach($product->aliases->take(3) as $alias)
            <span class="px-1.5 py-0.5 bg-slate-100 text-slate-600 text-[11px] rounded">{{ $alias->alias }}</span>
            @endforeach
            @if($product->aliases->count() > 3)
            <span class="text-[11px] text-slate-400" title="{{ $product->aliases->pluck('alias')->implode(', ') }}">+{{ $product->aliases->count() - 3 }}</span>
            @endif
        </div>
        @endif
    </td>
    <td class="px-4 py-3 text-sm text-slate-600">{{ $product->department->name }}</td>
    <td class="px-4 py-3 text-sm text-slate-900">{{ money($product->price_retail) }}</td>
    <td class="px-4 py-3">
        @if($product->is_active)
        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700"><i class="bi bi-check-circle mr-1"></i>Activo</span>
        @else
        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-700"><i class="bi bi-x-circle mr-1"></i>Inactivo</span>
        @endif
    </td>
    <td class="px-4 py-3"><div class="flex items-center justify-end gap-2">
        @can('update', $product)
        <a href="{{ route('products.edit', $product) }}" class="p-1.5 text-slate-600 hover:text-cyan-600 hover:bg-cyan-50 rounded transition-colors" title="Editar"><i class="bi bi-pencil"></i></a>
        @endcan
        @can('delete', $product)
        <button onclick="confirmDelete({{ $product->id }})" class="p-1.5 text-slate-600 hover:text-red-600 hover:bg-red-50 rounded transition-colors" title="Desactivar"><i class="bi bi-trash"></i></button>
        @endcan
    </div></td>
</tr>
@empty
<tr><td colspan="7" class="px-4 py-12 text-center text-slate-600"><div class="flex flex-col items-center gap-2"><i class="bi bi-box-seam text-4xl text-slate-300"></i><p>No se encontraron productos</p></div></td></tr>
@endforelse
