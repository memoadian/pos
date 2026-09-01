@extends('layouts.app')
@section('title', 'Venta #' . $sale->id)
@section('content')
@php
    $methodLabels = ['efectivo' => 'Efectivo', 'tarjeta' => 'Tarjeta', 'transferencia' => 'Transferencia', 'mixto' => 'Mixto'];
@endphp
<div class="space-y-6 max-w-3xl">
    @include('components.alerts')

    <div class="flex items-center justify-between">
        <div>
            <div class="flex items-center gap-2.5">
                <h1 class="text-xl font-semibold text-slate-900">Venta #{{ $sale->id }}</h1>
                @if($sale->isCancelled())
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700"><i class="bi bi-x-circle mr-1"></i>Cancelada</span>
                @else
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700"><i class="bi bi-check-circle mr-1"></i>Completada</span>
                @endif
            </div>
            <p class="text-sm text-slate-500 mt-1">{{ $sale->created_at->format('d/m/Y H:i') }}</p>
        </div>
        <a href="{{ route('sales.index') }}" class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100 rounded-lg transition-colors"><i class="bi bi-arrow-left"></i><span>Volver</span></a>
    </div>

    @if($sale->isCancelled())
    <div class="bg-red-50 border border-red-200 rounded-lg p-4">
        <div class="flex items-start gap-3">
            <i class="bi bi-x-circle-fill text-red-600 text-lg flex-shrink-0"></i>
            <div class="text-sm text-red-800">
                <p class="font-medium">Venta cancelada el {{ $sale->cancelled_at?->format('d/m/Y H:i') }}</p>
                <p class="mt-0.5">Por: {{ $sale->cancelledBy->name ?? '—' }}</p>
                @if($sale->cancellation_reason)
                    <p class="mt-0.5">Motivo: {{ $sale->cancellation_reason }}</p>
                @endif
            </div>
        </div>
    </div>
    @endif

    <div class="bg-white rounded-lg border border-slate-200 p-4 grid grid-cols-2 gap-x-6 gap-y-3 text-sm">
        <div><span class="text-slate-500">Sucursal:</span> <span class="text-slate-900 font-medium">{{ $sale->branch->name ?? '—' }}</span></div>
        <div><span class="text-slate-500">Cajero:</span> <span class="text-slate-900 font-medium">{{ $sale->user->name ?? $sale->user->username ?? '—' }}</span></div>
        <div><span class="text-slate-500">Cliente:</span> <span class="text-slate-900 font-medium">{{ $sale->client?->name ?? '— Sin cliente' }}</span></div>
        <div><span class="text-slate-500">Método de pago:</span> <span class="text-slate-900 font-medium">{{ $methodLabels[$sale->payment_method] ?? ucfirst($sale->payment_method) }}</span></div>
        <div><span class="text-slate-500">Caja:</span> <span class="text-slate-900 font-medium">#{{ $sale->cash_register_id }}</span></div>
    </div>

    <div class="bg-white rounded-lg border border-slate-200 overflow-hidden">
        <table class="w-full">
            <thead class="bg-slate-50 border-b border-slate-200">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-slate-600 uppercase">Producto</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-slate-600 uppercase">Tipo</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-slate-600 uppercase">Cantidad</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-slate-600 uppercase">P. Unitario</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-slate-600 uppercase">Total</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
                @foreach($sale->items as $item)
                <tr>
                    <td class="px-4 py-3 text-sm text-slate-900">{{ $item->product->name ?? 'Producto #' . $item->product_id }}</td>
                    <td class="px-4 py-3 text-sm text-slate-600">{{ $item->saleType->name ?? '—' }}</td>
                    <td class="px-4 py-3 text-sm text-slate-600 text-right tabular-nums">{{ rtrim(rtrim(number_format($item->quantity, 2), '0'), '.') }}</td>
                    <td class="px-4 py-3 text-sm text-slate-600 text-right tabular-nums">{{ money($item->unit_price) }}</td>
                    <td class="px-4 py-3 text-sm font-medium text-slate-900 text-right tabular-nums">{{ money($item->total) }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot class="bg-slate-50 border-t border-slate-200">
                <tr>
                    <td colspan="4" class="px-4 py-3 text-sm font-medium text-slate-600 text-right">Total</td>
                    <td class="px-4 py-3 text-sm font-semibold text-slate-900 text-right tabular-nums">{{ money($sale->total) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>

    @if(!$sale->isCancelled())
    @can('cancel', $sale)
    <div class="flex justify-end">
        <button type="button" onclick="document.getElementById('cancelSaleModal').classList.remove('hidden')"
            class="inline-flex items-center gap-2 px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition-colors">
            <i class="bi bi-x-circle"></i><span>Cancelar venta</span>
        </button>
    </div>

    <div id="cancelSaleModal" class="fixed inset-0 z-[90] hidden">
        <div class="absolute inset-0 bg-black/50" onclick="document.getElementById('cancelSaleModal').classList.add('hidden')"></div>
        <div class="relative flex items-center justify-center min-h-screen p-4">
            <form method="POST" action="{{ route('sales.cancel', $sale) }}" class="bg-white rounded-lg shadow-xl max-w-md w-full">
                @csrf
                <div class="p-6">
                    <div class="flex items-start gap-4">
                        <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center flex-shrink-0">
                            <i class="bi bi-exclamation-triangle text-red-600 text-lg"></i>
                        </div>
                        <div class="min-w-0">
                            <h3 class="text-base font-semibold text-slate-900">Cancelar venta #{{ $sale->id }}</h3>
                            <p class="mt-1 text-sm text-slate-500">Se restaurará el inventario y se revertirán los totales de la caja (aunque ya esté cerrada). Esta acción no se puede deshacer.</p>
                        </div>
                    </div>
                    <div class="mt-4">
                        <label for="reason" class="block text-sm font-medium text-slate-700 mb-1.5">Motivo (opcional)</label>
                        <textarea name="reason" id="reason" rows="3" maxlength="255"
                            class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 outline-none"
                            placeholder="Ej. Cobro duplicado, error de cajero..."></textarea>
                    </div>
                </div>
                <div class="flex items-center gap-3 px-6 py-4 bg-slate-50 border-t border-slate-200 rounded-b-lg">
                    <button type="button" onclick="document.getElementById('cancelSaleModal').classList.add('hidden')"
                        class="flex-1 px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-medium rounded-lg transition-colors">No, volver</button>
                    <button type="submit"
                        class="flex-1 px-4 py-2.5 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition-colors">Sí, cancelar venta</button>
                </div>
            </form>
        </div>
    </div>
    @endcan
    @endif
</div>
@endsection
