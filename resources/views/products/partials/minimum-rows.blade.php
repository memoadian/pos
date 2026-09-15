@forelse($products as $product)
<tr class="hover:bg-slate-50 transition-colors">
    <td class="px-4 py-3">
        <span class="text-sm font-medium text-slate-900">{{ $product->name }}</span>
        <div class="text-xs text-slate-400">{{ $product->barcode ?? 'N/A' }}</div>
        <input type="hidden" name="products[{{ $product->id }}][id]" value="{{ $product->id }}">
    </td>
    <td class="px-4 py-3 text-sm text-slate-600">{{ money($product->price_wholesale) }}</td>
    <td class="px-4 py-3">
        <input type="number" min="1" step="1" placeholder="Sin mínimo"
            name="products[{{ $product->id }}][min_wholesale_qty]"
            value="{{ old("products.{$product->id}.min_wholesale_qty", $product->min_wholesale_qty) }}"
            class="w-28 px-2 py-1.5 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none">
    </td>
    <td class="px-4 py-3 text-sm text-slate-600">{{ $product->price_super_wholesale > 0 ? money($product->price_super_wholesale) : '—' }}</td>
    <td class="px-4 py-3">
        <input type="number" min="1" step="1" placeholder="Sin mínimo"
            name="products[{{ $product->id }}][min_super_wholesale_qty]"
            value="{{ old("products.{$product->id}.min_super_wholesale_qty", $product->min_super_wholesale_qty) }}"
            class="w-28 px-2 py-1.5 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none">
    </td>
</tr>
@empty
<tr><td colspan="4" class="px-4 py-12 text-center text-slate-600"><div class="flex flex-col items-center gap-2"><i class="bi bi-box-seam text-4xl text-slate-300"></i><p>No se encontraron productos</p></div></td></tr>
@endforelse
