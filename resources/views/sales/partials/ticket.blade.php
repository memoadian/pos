<div class="text-center mb-2">
    <p class="font-bold text-sm">{{ setting('business_name', setting('site_name')) }}</p>
    @if(setting('business_address'))
        <p>{{ setting('business_address') }}</p>
    @endif
    @if(setting('business_phone'))
        <p>{{ setting('business_phone') }}</p>
    @endif
    @if(setting('business_tax_id'))
        <p>RFC: {{ setting('business_tax_id') }}</p>
    @endif
    <p>{{ $sale->branch->name ?? '—' }}</p>
</div>
<div class="border-t border-dashed border-slate-400 my-2"></div>
<div>Ticket: #{{ $sale->id }}</div>
<div>Fecha: {{ $sale->created_at->format('d/m/Y H:i') }}</div>
<div>Cajero: {{ $sale->user->name ?? $sale->user->username ?? '—' }}</div>
@if($sale->isCancelled())
<div class="text-center font-bold mt-2">*** VENTA CANCELADA ***</div>
@endif
<div class="border-t border-dashed border-slate-400 my-2"></div>
@foreach($sale->items as $item)
@php $unit = $item->saleType->base_unit ?? $item->product->unit_base ?? null; @endphp
<div class="flex justify-between gap-2">
    <span>{{ rtrim(rtrim(number_format($item->quantity, 2), '0'), '.') }}{{ $unit ? ' '.$unit : '' }} x {{ $item->product->name ?? 'Producto #'.$item->product_id }}</span>
    <span class="whitespace-nowrap">{{ money($item->total) }}</span>
</div>
@endforeach
<div class="border-t border-dashed border-slate-400 my-2"></div>
<div class="flex justify-between">
    <span>Subtotal:</span>
    <span>{{ money($sale->subtotal) }}</span>
</div>
<div class="flex justify-between font-bold text-sm">
    <span>Total:</span>
    <span>{{ money($sale->total) }}</span>
</div>
<div class="flex justify-between mt-1">
    <span>Pago:</span>
    <span>{{ $methodLabels[$sale->payment_method] ?? ucfirst($sale->payment_method) }}</span>
</div>
<div class="border-t border-dashed border-slate-400 my-2"></div>
<p class="text-center mt-2">{{ setting('ticket_footer', '¡Gracias por su compra!') }}</p>
