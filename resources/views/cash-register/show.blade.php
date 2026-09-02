@extends('layouts.app')
@section('title', 'Detalle de Caja')
@section('content')
<div class="max-w-6xl mx-auto space-y-6">
    @include('components.alerts')

    <div class="flex items-center gap-3">
        <a href="{{ route('cash-register.index') }}" class="p-2 hover:bg-slate-100 rounded-lg transition-colors">
            <i class="bi bi-arrow-left text-slate-600"></i>
        </a>
        <div class="flex-1">
            <h1 class="text-xl font-semibold text-slate-900">Detalle de Caja</h1>
            <p class="text-sm text-slate-500 mt-1">{{ $cashRegister->opened_at->format('d/m/Y H:i') }} - {{ $cashRegister->closed_at?->format('d/m/Y H:i') ?? 'En curso' }}</p>
        </div>
        @can('reopen', $cashRegister)
        <button type="button" onclick="reopenRegister()"
            class="inline-flex items-center gap-2 px-4 py-2 bg-amber-100 hover:bg-amber-200 text-amber-700 text-sm font-medium rounded-lg transition-colors">
            <i class="bi bi-unlock"></i>
            <span>Reabrir caja</span>
        </button>
        @endcan
        @can('delete', $cashRegister)
        <button type="button" onclick="deleteRegister()"
            class="inline-flex items-center gap-2 px-4 py-2 bg-red-100 hover:bg-red-200 text-red-700 text-sm font-medium rounded-lg transition-colors">
            <i class="bi bi-trash"></i>
            <span>Eliminar caja</span>
        </button>
        @endcan
    </div>

    @if(auth()->user()->hasRole('Admin') && $cashRegister->sales->isNotEmpty())
    <div class="bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 text-xs text-slate-500 flex items-center gap-2">
        <i class="bi bi-info-circle"></i>
        Esta caja tiene {{ $cashRegister->sales->count() }} venta(s) y no puede eliminarse. Cancela esas ventas primero si necesitas borrarla.
    </div>
    @endif

    {{-- Resumen de la Caja --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        {{-- Card Principal --}}
        <div class="bg-white rounded-lg border border-slate-200 overflow-hidden">
            <div class="bg-slate-50 px-6 py-4 border-b border-slate-200">
                <h2 class="font-semibold text-slate-900">Información General</h2>
            </div>
            <div class="p-6 space-y-4">
                <div class="flex items-center justify-between">
                    <span class="text-slate-600">Sucursal:</span>
                    <span class="font-medium">{{ $cashRegister->branch->name }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-slate-600">Usuario:</span>
                    <span class="font-medium">{{ $cashRegister->user->name }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-slate-600">Estado:</span>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium @if($cashRegister->status === 'abierta') bg-emerald-100 text-emerald-700 @else bg-slate-100 text-slate-700 @endif">
                        @if($cashRegister->status === 'abierta')
                            <i class="bi bi-check-circle mr-1"></i> Abierta
                        @else
                            <i class="bi bi-lock-fill mr-1"></i> Cerrada
                        @endif
                    </span>
                </div>
                <div class="flex items-center justify-between pt-4 border-t border-slate-200">
                    <span class="text-slate-600 text-sm">Apertura:</span>
                    <span class="text-sm">{{ $cashRegister->opened_at->format('d/m/Y H:i:s') }}</span>
                </div>
                @if($cashRegister->closed_at)
                <div class="flex items-center justify-between">
                    <span class="text-slate-600 text-sm">Cierre:</span>
                    <span class="text-sm">{{ $cashRegister->closed_at->format('d/m/Y H:i:s') }}</span>
                </div>
                @endif
            </div>
        </div>

        {{-- Resumen Financiero --}}
        <div class="bg-white rounded-lg border border-slate-200 overflow-hidden">
            <div class="bg-slate-50 px-6 py-4 border-b border-slate-200">
                <h2 class="font-semibold text-slate-900">Resumen Financiero</h2>
            </div>
            <div class="p-6 space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-slate-600">Monto Inicial:</span>
                    <span class="font-semibold">{{ money($stats['opening_amount']) }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-slate-600">Ventas Totales:</span>
                    <span class="font-semibold text-cyan-600">{{ money($stats['total_sales']) }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-slate-600">Ganancia:</span>
                    <span class="font-semibold text-emerald-600">{{ money($stats['total_profit']) }}</span>
                </div>
                <div class="border-t border-slate-200 pt-3 mt-3">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-slate-600">Monto Esperado:</span>
                        <span class="font-bold text-lg">{{ money($stats['expected_amount']) }}</span>
                    </div>
                    @if($cashRegister->closed_at)
                    <div class="flex items-center justify-between">
                        <span class="text-slate-600">Monto Contado:</span>
                        <span class="font-bold text-lg">{{ money($stats['closing_amount']) }}</span>
                    </div>
                    <div class="flex items-center justify-between pt-2 border-t border-slate-200">
                        <span class="text-slate-600">Diferencia:</span>
                        <span class="font-bold @if($stats['difference'] > 0) text-emerald-600 @elseif($stats['difference'] < 0) text-red-600 @else text-slate-600 @endif">
                            @if($stats['difference'] > 0) +@endif{{ money($stats['difference']) }}
                        </span>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Desglose por Método de Pago --}}
    <div class="bg-white rounded-lg border border-slate-200 overflow-hidden">
        <div class="bg-slate-50 px-6 py-4 border-b border-slate-200">
            <h2 class="font-semibold text-slate-900">Ventas por Método de Pago</h2>
        </div>
        <div class="p-6 grid grid-cols-3 gap-4">
            <div class="border rounded-lg p-4">
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                        <i class="bi bi-cash text-green-600"></i>
                    </div>
                    <span class="text-sm text-slate-600">Efectivo</span>
                </div>
                <p class="text-2xl font-bold">{{ money($stats['cash_sales']) }}</p>
            </div>
            <div class="border rounded-lg p-4">
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                        <i class="bi bi-credit-card text-blue-600"></i>
                    </div>
                    <span class="text-sm text-slate-600">Tarjeta</span>
                </div>
                <p class="text-2xl font-bold">{{ money($stats['card_sales']) }}</p>
            </div>
            <div class="border rounded-lg p-4">
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-10 h-10 bg-purple-100 rounded-full flex items-center justify-center">
                        <i class="bi bi-arrow-left-right text-purple-600"></i>
                    </div>
                    <span class="text-sm text-slate-600">Transferencia</span>
                </div>
                <p class="text-2xl font-bold">{{ money($stats['transfer_sales']) }}</p>
            </div>
        </div>
    </div>

    {{-- Movimientos de Caja --}}
    @if($cashRegister->movements->count() > 0)
    <div class="bg-white rounded-lg border border-slate-200 overflow-hidden">
        <div class="bg-slate-50 px-6 py-4 border-b border-slate-200">
            <h2 class="font-semibold text-slate-900">Movimientos de Caja</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-sm font-medium text-slate-700">Tipo</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-slate-700">Razón</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-slate-700">Usuario</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-slate-700">Monto</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-slate-700">Estado</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-slate-700">Fecha</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @foreach($cashRegister->movements as $movement)
                    <tr class="hover:bg-slate-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center gap-2">
                                <div class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold @if($movement->isIncome()) bg-green-100 text-green-600 @else bg-red-100 text-red-600 @endif">
                                    @if($movement->isIncome()) + @else - @endif
                                </div>
                                <span class="text-sm font-medium">{{ $movement->isIncome() ? 'Ingreso' : 'Retiro' }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm">{{ $movement->reason }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm">{{ $movement->user->name }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="font-semibold @if($movement->isIncome()) text-green-600 @else text-red-600 @endif">
                                @if($movement->isIncome()) + @endif{{ money($movement->amount) }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                @if($movement->isPending()) bg-yellow-100 text-yellow-800
                                @elseif($movement->isApproved()) bg-emerald-100 text-emerald-800
                                @else bg-red-100 text-red-800
                                @endif">
                                @if($movement->isPending()) Pendiente
                                @elseif($movement->isApproved()) Aprobado
                                @else Rechazado
                                @endif
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-slate-600">
                            {{ $movement->created_at->format('d/m/Y H:i') }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- Gastos --}}
    @if($cashRegister->expenses->count() > 0)
    <div class="bg-white rounded-lg border border-slate-200 overflow-hidden">
        <div class="bg-slate-50 px-6 py-4 border-b border-slate-200 flex items-center justify-between">
            <h2 class="font-semibold text-slate-900">Gastos ({{ $cashRegister->expenses->count() }})</h2>
            <span class="font-semibold text-red-600">-{{ money($cashRegister->total_expenses) }}</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-sm font-medium text-slate-700">Categoría</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-slate-700">Descripción</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-slate-700">Usuario</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-slate-700">Monto</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-slate-700">Fecha</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @foreach($cashRegister->expenses as $expense)
                    <tr class="hover:bg-slate-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-700">{{ $expense->categoryLabel() }}</span>
                        </td>
                        <td class="px-6 py-4"><span class="text-sm">{{ $expense->description }}</span></td>
                        <td class="px-6 py-4"><span class="text-sm">{{ $expense->user->name ?? '—' }}</span></td>
                        <td class="px-6 py-4"><span class="font-semibold text-red-600">-{{ money($expense->amount) }}</span></td>
                        <td class="px-6 py-4 text-sm text-slate-600">{{ $expense->created_at->format('d/m/Y H:i') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- Ventas --}}
    @if($cashRegister->sales->count() > 0)
    <div class="bg-white rounded-lg border border-slate-200 overflow-hidden">
        <div class="bg-slate-50 px-6 py-4 border-b border-slate-200">
            <h2 class="font-semibold text-slate-900">Ventas ({{ $cashRegister->sales->count() }})</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-sm font-medium text-slate-700">#ID</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-slate-700">Cliente</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-slate-700">Método</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-slate-700">Subtotal</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-slate-700">Ganancia</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-slate-700">Fecha</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @foreach($cashRegister->sales as $sale)
                    <tr class="hover:bg-slate-50 {{ $sale->isCancelled() ? 'bg-red-50/40' : '' }}">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="font-medium text-slate-900">#{{ $sale->id }}</span>
                            @if($sale->isCancelled())
                                <span class="ml-1 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700">Cancelada</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm">{{ $sale->client?->name ?? '— Sin cliente' }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                @if($sale->payment_method === 'efectivo') bg-green-100 text-green-800
                                @elseif($sale->payment_method === 'tarjeta') bg-blue-100 text-blue-800
                                @elseif($sale->payment_method === 'transferencia') bg-purple-100 text-purple-800
                                @endif">
                                @switch($sale->payment_method)
                                    @case('efectivo') Efectivo @break
                                    @case('tarjeta') Tarjeta @break
                                    @case('transferencia') Transferencia @break
                                @endswitch
                            </span>
                        </td>
                        <td class="px-6 py-4 font-semibold">{{ money($sale->subtotal) }}</td>
                        <td class="px-6 py-4 font-semibold text-emerald-600">{{ money($sale->profit) }}</td>
                        <td class="px-6 py-4 text-sm text-slate-600">
                            {{ $sale->created_at->format('d/m/Y H:i') }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- Notas --}}
    @if($cashRegister->opening_notes || $cashRegister->closing_notes)
    <div class="bg-white rounded-lg border border-slate-200 overflow-hidden">
        <div class="bg-slate-50 px-6 py-4 border-b border-slate-200">
            <h2 class="font-semibold text-slate-900">Notas</h2>
        </div>
        <div class="p-6 space-y-4">
            @if($cashRegister->opening_notes)
            <div>
                <p class="text-sm font-medium text-slate-700 mb-2">Notas de Apertura</p>
                <p class="text-slate-600 bg-slate-50 rounded-lg p-3 text-sm">{{ $cashRegister->opening_notes }}</p>
            </div>
            @endif
            @if($cashRegister->closing_notes)
            <div>
                <p class="text-sm font-medium text-slate-700 mb-2">Notas de Cierre</p>
                <p class="text-slate-600 bg-slate-50 rounded-lg p-3 text-sm">{{ $cashRegister->closing_notes }}</p>
            </div>
            @endif
        </div>
    </div>
    @endif
</div>

@can('reopen', $cashRegister)
<form id="reopenForm" method="POST" action="{{ route('cash-register.reopen', $cashRegister) }}" class="hidden">@csrf</form>
@endcan
@endsection
@section('scripts')
@can('reopen', $cashRegister)
<script>
    function reopenRegister() {
        ConfirmModal.show({
            title: 'Reabrir caja',
            message: 'Se limpiarán los datos del cierre y la caja volverá a estado "abierta". El cajero podría quedar con más de una caja abierta a la vez.',
            confirmText: 'Reabrir',
            danger: false,
            onConfirm: () => document.getElementById('reopenForm').submit(),
        });
    }
</script>
@endcan
@can('delete', $cashRegister)
<script>
    function deleteRegister() {
        ConfirmModal.confirmDelete({
            action: '{{ route('cash-register.destroy', $cashRegister) }}',
            title: 'Eliminar caja',
            message: 'Se eliminará la caja #{{ $cashRegister->id }} y sus movimientos y gastos. Esta acción no se puede deshacer.',
            confirmText: 'Eliminar caja',
        });
    }
</script>
@endcan
@endsection
