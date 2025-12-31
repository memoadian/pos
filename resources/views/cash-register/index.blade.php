@extends('layouts.app')
@section('title', 'Mi Caja')
@section('content')
    <div class="max-w-6xl mx-auto space-y-6">
        @include('components.alerts')

        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-semibold text-slate-900">Mi Caja</h1>
                <p class="text-sm text-slate-500 mt-1">Gestiona tu caja registradora</p>
            </div>
            @if (auth()->user()->hasRole(['Admin', 'Admin']))
                <a class="inline-flex items-center gap-2 px-4 py-2 text-sm text-cyan-600 hover:text-cyan-700 font-medium"
                    href="{{ route('cash-registers.history') }}">
                    <i class="bi bi-list-check"></i>
                    <span>Ver Historial</span>
                </a>
            @endif
        </div>

        @if (auth()->user()->hasRole(['Admin', 'Admin']) && !auth()->user()->branch_id)
            {{-- Modo Admin sin sucursal --}}
            <div class="bg-white rounded-lg border border-slate-200 p-8 text-center">
                <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="bi bi-shield-check text-blue-600 text-3xl"></i>
                </div>
                <h2 class="text-lg font-semibold text-slate-900 mb-2">Modo Administrador</h2>
                <p class="text-slate-500 mb-6">No tienes una sucursal asignada. Puedes abrir una caja temporal para probar
                    el POS.</p>
                <a class="inline-flex items-center gap-2 px-6 py-3 bg-cyan-600 hover:bg-cyan-700 text-white font-medium rounded-lg transition-colors"
                    href="{{ route('cash-register.open') }}">
                    <i class="bi bi-plus-lg"></i>
                    <span>Abrir Caja Temporal</span>
                </a>
            </div>
        @elseif($openRegister)
            {{-- Caja Abierta --}}
            {{-- Alerta de movimientos pendientes --}}
            @if ($openRegister->hasPendingMovements())
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 flex items-start gap-3">
                    <div class="flex-shrink-0">
                        <i class="bi bi-exclamation-triangle text-yellow-600 text-lg mt-0.5"></i>
                    </div>
                    <div>
                        <h3 class="font-semibold text-yellow-900">Movimientos Pendientes de Aprobación</h3>
                        <p class="text-sm text-yellow-800 mt-1">Hay {{ $openRegister->movements()->pending()->count() }}
                            movimiento(s) esperando aprobación del administrador.</p>
                    </div>
                </div>
            @endif

            {{-- Card Principal de Caja Abierta --}}
            <div class="bg-white rounded-lg border border-emerald-200 overflow-hidden">
                <div class="bg-emerald-50 px-6 py-4 border-b border-emerald-200">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-emerald-100 rounded-full flex items-center justify-center">
                                <i class="bi bi-cash-stack text-emerald-600 text-xl"></i>
                            </div>
                            <div>
                                <h2 class="font-semibold text-emerald-900">Caja Abierta</h2>
                                <p class="text-sm text-emerald-700">{{ $openRegister->branch->name }}</p>
                            </div>
                        </div>
                        <span
                            class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-emerald-100 text-emerald-700">
                            <i class="bi bi-check-circle mr-1"></i> Activa
                        </span>
                    </div>
                </div>

                {{-- Totales Principales --}}
                <div class="p-6 grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="bg-slate-50 rounded-lg p-4">
                        <p class="text-xs text-slate-500 uppercase tracking-wide">Monto Inicial</p>
                        <p class="text-xl font-bold text-slate-900 mt-1">
                            ${{ number_format($openRegister->opening_amount, 2) }}</p>
                    </div>
                    <div class="bg-slate-50 rounded-lg p-4">
                        <p class="text-xs text-slate-500 uppercase tracking-wide">Total Ventas</p>
                        <p class="text-xl font-bold text-cyan-600 mt-1">${{ number_format($openRegister->total_sales, 2) }}
                        </p>
                    </div>
                    <div class="bg-slate-50 rounded-lg p-4">
                        <p class="text-xs text-slate-500 uppercase tracking-wide">Ganancia</p>
                        <p class="text-xl font-bold text-emerald-600 mt-1">
                            ${{ number_format($openRegister->total_profit, 2) }}</p>
                    </div>
                    <div class="bg-slate-50 rounded-lg p-4">
                        <p class="text-xs text-slate-500 uppercase tracking-wide">Transacciones</p>
                        <p class="text-xl font-bold text-slate-900 mt-1">{{ $openRegister->sales->count() }}</p>
                    </div>
                </div>

                {{-- Desglose por Método de Pago --}}
                <div class="px-6 py-4 border-t border-slate-200 bg-slate-50">
                    <h3 class="text-sm font-semibold text-slate-900 mb-3">Ventas por Método de Pago</h3>
                    <div class="grid grid-cols-3 gap-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                                <i class="bi bi-cash text-green-600"></i>
                            </div>
                            <div>
                                <p class="text-xs text-slate-500">Efectivo</p>
                                <p class="font-semibold text-slate-900">${{ number_format($openRegister->cash_sales, 2) }}
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                                <i class="bi bi-credit-card text-blue-600"></i>
                            </div>
                            <div>
                                <p class="text-xs text-slate-500">Tarjeta</p>
                                <p class="font-semibold text-slate-900">${{ number_format($openRegister->card_sales, 2) }}
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-purple-100 rounded-full flex items-center justify-center">
                                <i class="bi bi-arrow-left-right text-purple-600"></i>
                            </div>
                            <div>
                                <p class="text-xs text-slate-500">Transferencia</p>
                                <p class="font-semibold text-slate-900">
                                    ${{ number_format($openRegister->transfer_sales, 2) }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Botones de Acción --}}
                <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex items-center gap-3">
                    <a class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-cyan-600 hover:bg-cyan-700 text-white text-sm font-medium rounded-lg transition-colors"
                        href="{{ route('pos.index') }}">
                        <i class="bi bi-cart3"></i>
                        <span>Ir al Punto de Venta</span>
                    </a>
                    <button
                        class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-amber-100 hover:bg-amber-200 text-amber-700 text-sm font-medium rounded-lg transition-colors"
                        type="button" onclick="openMovementModal()">
                        <i class="bi bi-arrow-up-down"></i>
                        <span>Entrada/Salida</span>
                    </button>
                    <a class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-red-100 hover:bg-red-200 text-red-700 text-sm font-medium rounded-lg transition-colors"
                        href="{{ route('cash-register.close') }}">
                        <i class="bi bi-x-circle"></i>
                        <span>Cerrar Caja</span>
                    </a>
                </div>

                <div class="px-6 py-3 bg-slate-100 text-xs text-slate-500">
                    <i class="bi bi-clock mr-1"></i>
                    Abierta: {{ $openRegister->opened_at->format('d/m/Y H:i') }}
                </div>
            </div>

            {{-- Movimientos Pendientes (Solo para Admin) --}}
            @if (auth()->user()->hasRole(['Admin', 'Admin']))
                @php
                    $pendingMovements = $openRegister
                        ->movements()
                        ->pending()
                        ->with(['user'])
                        ->get();
                @endphp
                @if ($pendingMovements->count() > 0)
                    <div class="bg-white rounded-lg border border-yellow-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-yellow-200 bg-yellow-50">
                            <h3 class="font-semibold text-yellow-900">Movimientos Pendientes de Aprobación</h3>
                        </div>
                        <div class="divide-y divide-slate-200">
                            @foreach ($pendingMovements as $movement)
                                <div class="px-6 py-4 flex items-center justify-between hover:bg-slate-50">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-3">
                                            <div
                                                class="w-8 h-8 rounded-full flex items-center justify-center @if ($movement->isIncome()) bg-green-100 @else bg-red-100 @endif">
                                                <i
                                                    class="bi @if ($movement->isIncome()) bi-plus text-green-600 @else bi-dash text-red-600 @endif"></i>
                                            </div>
                                            <div>
                                                <p class="font-medium text-slate-900">
                                                    {{ $movement->isIncome() ? 'Ingreso' : 'Retiro' }} -
                                                    {{ $movement->reason }}
                                                </p>
                                                <p class="text-sm text-slate-500">Solicitado por:
                                                    {{ $movement->user->name }}</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-right mr-4">
                                        <p class="font-semibold text-slate-900">${{ number_format($movement->amount, 2) }}
                                        </p>
                                        <p class="text-xs text-slate-500">{{ $movement->created_at->format('H:i') }}</p>
                                    </div>
                                    <div class="flex gap-2">
                                        <button
                                            class="px-3 py-1.5 bg-emerald-100 hover:bg-emerald-200 text-emerald-700 text-xs font-medium rounded transition-colors"
                                            type="button" onclick="approveMovement({{ $movement->id }})" title="Aprobar">
                                            <i class="bi bi-check-lg"></i>
                                        </button>
                                        <button
                                            class="px-3 py-1.5 bg-red-100 hover:bg-red-200 text-red-700 text-xs font-medium rounded transition-colors"
                                            type="button" onclick="rejectMovement({{ $movement->id }})" title="Rechazar">
                                            <i class="bi bi-x-lg"></i>
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            @endif
        @else
            {{-- Sin Caja Abierta --}}
            <div class="bg-white rounded-lg border border-slate-200 p-8 text-center">
                <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="bi bi-cash-stack text-slate-400 text-3xl"></i>
                </div>
                <h2 class="text-lg font-semibold text-slate-900 mb-2">No tienes una caja abierta</h2>
                <p class="text-slate-500 mb-6">Abre una caja para comenzar a realizar ventas</p>
                <a class="inline-flex items-center gap-2 px-6 py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-lg transition-colors"
                    href="{{ route('cash-register.open') }}">
                    <i class="bi bi-plus-lg"></i>
                    <span>Abrir Caja</span>
                </a>
            </div>
        @endif

        @if ($recentRegisters->count() > 0)
            {{-- Historial Reciente --}}
            <div class="bg-white rounded-lg border border-slate-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200">
                    <h3 class="font-semibold text-slate-900">Cajas Recientes</h3>
                </div>
                <div class="divide-y divide-slate-200">
                    @foreach ($recentRegisters as $register)
                        <div class="px-6 py-4 flex items-center justify-between hover:bg-slate-50 cursor-pointer"
                            onclick="window.location.href='{{ route('cash-register.show', $register->id) }}'">
                            <div>
                                <p class="font-medium text-slate-900">{{ $register->opened_at->format('d/m/Y') }}</p>
                                <p class="text-sm text-slate-500">{{ $register->opened_at->format('H:i') }} -
                                    {{ $register->closed_at?->format('H:i') ?? 'En curso' }}</p>
                            </div>
                            <div class="text-right">
                                <p class="font-semibold text-slate-900">${{ number_format($register->total_sales, 2) }}
                                </p>
                                <p class="text-sm text-emerald-600">+${{ number_format($register->total_profit, 2) }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    {{-- Modal para Registrar Movimiento --}}
    <div class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" id="movementModal">
        <div class="bg-white rounded-lg shadow-lg max-w-md w-full mx-4">
            <div class="px-6 py-4 border-b border-slate-200">
                <h2 class="text-lg font-semibold text-slate-900">Registrar Movimiento</h2>
            </div>

            <form class="space-y-4 p-6" id="movementForm">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Tipo</label>
                    <select
                        class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none"
                        id="type" name="type" required>
                        <option value="">-- Selecciona --</option>
                        <option value="ingreso">Ingreso (Fondo cambio, etc)</option>
                        <option value="retiro">Retiro (Gasto menor, etc)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Monto</label>
                    <input
                        class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none"
                        id="amount" type="number" name="amount" step="0.01" min="0.01" required
                        placeholder="0.00">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Razón</label>
                    <input
                        class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none"
                        id="reason" type="text" name="reason" required maxlength="255"
                        placeholder="Ej: Fondo cambio">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Notas (Opcional)</label>
                    <textarea
                        class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none"
                        id="notes" name="notes" maxlength="500" rows="2" placeholder="Notas adicionales..."></textarea>
                </div>

                <div class="flex gap-3 pt-4">
                    <button
                        class="flex-1 px-4 py-2 bg-cyan-600 hover:bg-cyan-700 text-white font-medium rounded-lg transition-colors"
                        type="submit">
                        Registrar
                    </button>
                    <button
                        class="flex-1 px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-medium rounded-lg transition-colors"
                        type="button" onclick="closeMovementModal()">
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openMovementModal() {
            document.getElementById('movementModal').classList.remove('hidden');
        }

        function closeMovementModal() {
            document.getElementById('movementModal').classList.add('hidden');
            document.getElementById('movementForm').reset();
        }

        document.getElementById('movementForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            const button = this.querySelector('button[type="submit"]');
            button.disabled = true;
            button.textContent = 'Registrando...';

            try {
                const response = await fetch('{{ route('cash-register.movement.add') }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                    },
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    // Mostrar alerta de éxito
                    const alertDiv = document.createElement('div');
                    alertDiv.className =
                        'bg-emerald-50 border border-emerald-200 rounded-lg p-4 text-emerald-900 mb-4';
                    alertDiv.innerHTML = `<i class="bi bi-check-circle mr-2"></i> ${data.message}`;
                    document.querySelector('.max-w-6xl').prepend(alertDiv);

                    setTimeout(() => alertDiv.remove(), 4000);
                    closeMovementModal();

                    // Recargar página después de 1 segundo
                    setTimeout(() => location.reload(), 1000);
                } else {
                    alert('Error: ' + data.message);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error al registrar el movimiento');
            } finally {
                button.disabled = false;
                button.textContent = 'Registrar';
            }
        });

        async function approveMovement(movementId) {
            if (!confirm('¿Deseas aprobar este movimiento?')) return;

            const button = event.target.closest('button');
            button.disabled = true;

            try {
                const response = await fetch(`{{ route('cash-register.movement.approve', ':id') }}`.replace(':id',
                    movementId), {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ||
                            document.querySelector('input[name="_token"]')?.value,
                    }
                });

                const data = await response.json();

                if (data.success) {
                    // Mostrar alerta
                    const alertDiv = document.createElement('div');
                    alertDiv.className = 'bg-emerald-50 border border-emerald-200 rounded-lg p-4 text-emerald-900 mb-4';
                    alertDiv.innerHTML = `<i class="bi bi-check-circle mr-2"></i> ${data.message}`;
                    document.querySelector('.max-w-6xl').prepend(alertDiv);

                    setTimeout(() => location.reload(), 1000);
                } else {
                    alert('Error: ' + data.message);
                    button.disabled = false;
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error al aprobar el movimiento');
                button.disabled = false;
            }
        }

        async function rejectMovement(movementId) {
            if (!confirm('¿Deseas rechazar este movimiento?')) return;

            const button = event.target.closest('button');
            button.disabled = true;

            try {
                const response = await fetch(`{{ route('cash-register.movement.reject', ':id') }}`.replace(':id',
                    movementId), {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ||
                            document.querySelector('input[name="_token"]')?.value,
                    }
                });

                const data = await response.json();

                if (data.success) {
                    // Mostrar alerta
                    const alertDiv = document.createElement('div');
                    alertDiv.className = 'bg-emerald-50 border border-emerald-200 rounded-lg p-4 text-emerald-900 mb-4';
                    alertDiv.innerHTML = `<i class="bi bi-check-circle mr-2"></i> ${data.message}`;
                    document.querySelector('.max-w-6xl').prepend(alertDiv);

                    setTimeout(() => location.reload(), 1000);
                } else {
                    alert('Error: ' + data.message);
                    button.disabled = false;
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error al rechazar el movimiento');
                button.disabled = false;
            }
        }

        // Cerrar modal al presionar ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeMovementModal();
            }
        });

        // Cerrar modal al hacer click afuera
        document.getElementById('movementModal')?.addEventListener('click', function(e) {
            if (e.target === this) {
                closeMovementModal();
            }
        });
    </script>
@endsection
