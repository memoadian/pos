@forelse($movements as $mov)
<tr class="hover:bg-slate-50">
    <td class="px-4 py-3 text-sm text-slate-600">{{ $mov->created_at->format('d/m/Y H:i') }}</td>
    <td class="px-4 py-3">
        @if($mov->type === 'IN')<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700">Entrada</span>
        @elseif($mov->type === 'OUT')<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700">Salida</span>
        @else<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-700">Ajuste</span>
        @endif
    </td>
    <td class="px-4 py-3 text-sm text-slate-900">{{ $mov->product->name }}</td>
    <td class="px-4 py-3 text-sm font-medium text-slate-900">{{ number_format($mov->quantity, 2) }}</td>
    <td class="px-4 py-3 text-sm text-slate-600">{{ $mov->reason }}</td>
    <td class="px-4 py-3 text-sm text-slate-600">{{ $mov->user->name }}</td>
</tr>
@empty
<tr><td colspan="6" class="px-4 py-12 text-center text-slate-600"><div class="flex flex-col items-center gap-2"><i class="bi bi-arrow-left-right text-4xl text-slate-300"></i><p>No hay movimientos registrados</p></div></td></tr>
@endforelse
