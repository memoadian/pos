@php
    $methodLabels = ['efectivo' => 'Efectivo', 'tarjeta' => 'Tarjeta', 'transferencia' => 'Transferencia', 'mixto' => 'Mixto'];
@endphp
@forelse($sales as $sale)
<tr class="hover:bg-slate-50 {{ $sale->isCancelled() ? 'bg-red-50/40' : '' }}">
    <td class="px-4 py-3 text-sm font-medium text-slate-900">#{{ $sale->id }}</td>
    <td class="px-4 py-3 text-sm text-slate-600 whitespace-nowrap">{{ $sale->created_at->format('d/m/Y H:i') }}</td>
    <td class="px-4 py-3 text-sm text-slate-600">{{ $sale->branch->name ?? '—' }}</td>
    <td class="px-4 py-3 text-sm text-slate-600">{{ $sale->user->name ?? $sale->user->username ?? '—' }}</td>
    <td class="px-4 py-3 text-sm text-slate-600">{{ $sale->client?->name ?? '— Sin cliente' }}</td>
    <td class="px-4 py-3 text-sm text-slate-600">{{ $methodLabels[$sale->payment_method] ?? ucfirst($sale->payment_method) }}</td>
    <td class="px-4 py-3 text-sm font-semibold text-slate-900 text-right tabular-nums">{{ money($sale->total) }}</td>
    <td class="px-4 py-3">
        @if($sale->isCancelled())
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700"><i class="bi bi-x-circle mr-1"></i>Cancelada</span>
        @else
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700"><i class="bi bi-check-circle mr-1"></i>Completada</span>
        @endif
    </td>
    <td class="px-4 py-3">
        <div class="flex items-center justify-end gap-2">
            <a href="{{ route('sales.show', $sale) }}" class="p-1.5 text-slate-600 hover:text-cyan-600 hover:bg-cyan-50 rounded transition-colors" title="Ver detalle"><i class="bi bi-eye"></i></a>
            @if(!$sale->isCancelled())
            @can('cancel', $sale)
            <a href="{{ route('sales.show', $sale) }}" class="p-1.5 text-slate-600 hover:text-red-600 hover:bg-red-50 rounded transition-colors" title="Cancelar venta"><i class="bi bi-x-circle"></i></a>
            @endcan
            @endif
        </div>
    </td>
</tr>
@empty
<tr><td colspan="9" class="px-4 py-12 text-center text-slate-600"><div class="flex flex-col items-center gap-2"><i class="bi bi-receipt text-4xl text-slate-300"></i><p>No se encontraron ventas</p></div></td></tr>
@endforelse
