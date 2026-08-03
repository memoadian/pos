@extends('layouts.app')
@section('title', 'Cerrar Caja')
@section('content')
<div class="max-w-2xl mx-auto space-y-6">
    @include('components.alerts')

    <div class="flex items-center gap-3">
        <a href="{{ route('cash-register.index') }}" class="p-2 hover:bg-slate-100 rounded-lg transition-colors">
            <i class="bi bi-arrow-left text-slate-600"></i>
        </a>
        <div>
            <h1 class="text-xl font-semibold text-slate-900">Cerrar Caja</h1>
            <p class="text-sm text-slate-500 mt-1">Finaliza tu turno de ventas</p>
        </div>
    </div>

    {{-- Resumen del Turno --}}
    <div class="bg-white rounded-lg border border-slate-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200 bg-slate-50">
            <h3 class="font-semibold text-slate-900">Resumen del Turno</h3>
            <p class="text-sm text-slate-500">{{ $openRegister->opened_at->format('d/m/Y H:i') }} - Ahora</p>
        </div>

        <div class="p-6 space-y-4">
            {{-- Montos --}}
            <div class="grid grid-cols-2 gap-4">
                <div class="bg-slate-50 rounded-lg p-4">
                    <p class="text-xs text-slate-500 uppercase tracking-wide">Monto Inicial</p>
                    <p class="text-xl font-bold text-slate-900 mt-1">${{ number_format($stats['opening_amount'], 2) }}</p>
                </div>
                <div class="bg-cyan-50 rounded-lg p-4">
                    <p class="text-xs text-cyan-600 uppercase tracking-wide">Total Ventas</p>
                    <p class="text-xl font-bold text-cyan-700 mt-1">${{ number_format($stats['total_sales'], 2) }}</p>
                </div>
            </div>

            {{-- Ventas por Método de Pago --}}
            @if($salesByMethod->count() > 0)
            <div class="border-t border-slate-200 pt-4">
                <p class="text-sm font-medium text-slate-700 mb-3">Desglose por Método de Pago</p>
                <div class="space-y-2">
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-slate-600">
                            <i class="bi bi-cash text-green-600 mr-1"></i> Efectivo
                        </span>
                        <span class="font-medium">${{ number_format($stats['cash_sales'], 2) }}</span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-slate-600">
                            <i class="bi bi-credit-card text-blue-600 mr-1"></i> Tarjeta
                        </span>
                        <span class="font-medium">${{ number_format($stats['card_sales'], 2) }}</span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-slate-600">
                            <i class="bi bi-arrow-left-right text-purple-600 mr-1"></i> Transferencia
                        </span>
                        <span class="font-medium">${{ number_format($stats['transfer_sales'], 2) }}</span>
                    </div>
                </div>
            </div>
            @endif

            {{-- Movimientos Aprobados --}}
            @if($stats['total_approved_incomes'] > 0 || $stats['total_approved_withdrawals'] > 0)
            <div class="border-t border-slate-200 pt-4">
                <p class="text-sm font-medium text-slate-700 mb-3">Movimientos Aprobados</p>
                <div class="space-y-2">
                    @if($stats['total_approved_incomes'] > 0)
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-slate-600">
                            <i class="bi bi-plus-circle text-green-600 mr-1"></i> Ingresos
                        </span>
                        <span class="font-medium text-green-600">+${{ number_format($stats['total_approved_incomes'], 2) }}</span>
                    </div>
                    @endif
                    @if($stats['total_approved_withdrawals'] > 0)
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-slate-600">
                            <i class="bi bi-dash-circle text-red-600 mr-1"></i> Retiros
                        </span>
                        <span class="font-medium text-red-600">-${{ number_format($stats['total_approved_withdrawals'], 2) }}</span>
                    </div>
                    @endif
                </div>
            </div>
            @endif

            {{-- Cálculo del Monto Esperado --}}
            <div class="bg-blue-50 rounded-lg p-4 border border-blue-200 text-sm">
                <p class="text-blue-700 font-medium mb-2">Cálculo del Monto Esperado:</p>
                <div class="space-y-1 text-xs text-blue-600 font-mono">
                    <div class="flex justify-between">
                        <span>Monto Inicial:</span>
                        <span>${{ number_format($stats['opening_amount'], 2) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>+ Ventas en Efectivo:</span>
                        <span>+${{ number_format($stats['cash_sales'], 2) }}</span>
                    </div>
                    @if($stats['total_approved_incomes'] > 0)
                    <div class="flex justify-between">
                        <span>+ Ingresos Aprobados:</span>
                        <span>+${{ number_format($stats['total_approved_incomes'], 2) }}</span>
                    </div>
                    @endif
                    @if($stats['total_approved_withdrawals'] > 0)
                    <div class="flex justify-between">
                        <span>- Retiros Aprobados:</span>
                        <span>-${{ number_format($stats['total_approved_withdrawals'], 2) }}</span>
                    </div>
                    @endif
                    <div class="border-t border-blue-200 pt-1 mt-1 flex justify-between font-medium">
                        <span>= Total Esperado:</span>
                        <span>${{ number_format($stats['expected_amount'], 2) }}</span>
                    </div>
                </div>
            </div>

            {{-- Monto Esperado Principal --}}
            <div class="bg-emerald-50 rounded-lg p-4 border border-emerald-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-emerald-700">Monto Esperado en Caja</p>
                        <p class="text-xs text-emerald-600 mt-0.5">Según cálculo anterior</p>
                    </div>
                    <p class="text-2xl font-bold text-emerald-700">${{ number_format($stats['expected_amount'], 2) }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Alerta de movimientos pendientes --}}
    @if($openRegister->hasPendingMovements())
        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
            <div class="flex gap-3">
                <i class="bi bi-exclamation-circle text-red-600"></i>
                <div class="text-sm text-red-800">
                    <p class="font-medium">No puedes cerrar la caja</p>
                    <p class="mt-1">Hay {{ $openRegister->movements()->pending()->count() }} movimiento(s) pendiente(s) de aprobación. El administrador debe aprobarlos o rechazarlos primero.</p>
                </div>
            </div>
        </div>
    @endif

    {{-- Formulario de Cierre --}}
    <form method="POST" action="{{ route('cash-register.store-close') }}" class="bg-white rounded-lg border border-slate-200" @if($openRegister->hasPendingMovements()) style="opacity: 0.5; pointer-events: none;" @endif>
        @csrf

        <div class="p-6 space-y-5">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">
                    Monto Final en Caja <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-slate-500">$</span>
                    <input type="number"
                           name="closing_amount"
                           value="{{ old('closing_amount', $stats['expected_amount']) }}"
                           step="0.01"
                           min="0"
                           required
                           autofocus
                           class="w-full pl-8 pr-4 py-3 text-lg border border-slate-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none transition @error('closing_amount') border-red-500 @enderror"
                           @if($openRegister->hasPendingMovements()) disabled @endif>
                </div>
                <p class="mt-1 text-xs text-slate-500">Ingresa el dinero que hay físicamente en la caja</p>
                @error('closing_amount')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">
                    Notas de Cierre (Opcional)
                </label>
                <textarea name="closing_notes"
                          maxlength="500"
                          class="w-full px-4 py-3 border border-slate-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none transition @error('closing_notes') border-red-500 @enderror"
                          rows="3"
                          placeholder="Ej: Diferencia por cambio, cliente devolución, etc."
                          @if($openRegister->hasPendingMovements()) disabled @endif>{{ old('closing_notes') }}</textarea>
                @error('closing_notes')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="bg-amber-50 border border-amber-200 rounded-lg p-4">
                <div class="flex gap-3">
                    <i class="bi bi-exclamation-triangle text-amber-600"></i>
                    <div class="text-sm text-amber-800">
                        <p class="font-medium">Esta acción es irreversible</p>
                        <p class="mt-1">Una vez cerrada la caja, no podrás realizar más ventas hasta abrir una nueva.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-3 px-6 py-4 bg-slate-50 border-t border-slate-200 rounded-b-lg">
            <button type="submit" class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition-colors">
                <i class="bi bi-x-circle"></i>
                <span>Cerrar Caja</span>
            </button>
            <a href="{{ route('cash-register.index') }}" class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-medium rounded-lg transition-colors">
                <span>Cancelar</span>
            </a>
        </div>
    </form>
</div>
@endsection
