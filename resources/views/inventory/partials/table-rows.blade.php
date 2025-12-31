@forelse($inventories as $inv)
<tr class="hover:bg-slate-50 {{ $inv->stock_quantity <= 10 ? 'bg-yellow-50' : '' }}">
    <td class="px-4 py-3"><span class="text-sm font-medium text-slate-900">{{ $inv->product->name }}</span><br><span class="text-xs text-slate-500">{{ $inv->product->barcode ?? 'Sin código' }}</span></td>
    <td class="px-4 py-3 text-sm text-slate-600">{{ $inv->product->department->name }}</td>
    <td class="px-4 py-3"><span class="text-sm font-medium {{ $inv->stock_quantity <= 10 ? 'text-yellow-700' : 'text-slate-900' }}">{{ number_format($inv->stock_quantity, 2) }}</span></td>
    <td class="px-4 py-3 text-sm text-slate-600">{{ $inv->product->unit_base }}</td>
</tr>
@empty
<tr><td colspan="4" class="px-4 py-12 text-center text-slate-600"><div class="flex flex-col items-center gap-2"><i class="bi bi-inbox text-4xl text-slate-300"></i><p>No hay inventario registrado</p></div></td></tr>
@endforelse
