@extends('layouts.app')
@section('title', 'Historial de Cajas')
@section('content')
<div class="max-w-7xl mx-auto space-y-6">
    @include('components.alerts')

    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-semibold text-slate-900">Historial de Cajas</h1>
            <p class="text-sm text-slate-500 mt-1">Registro de todas las cajas registradas</p>
        </div>
        <a href="{{ route('cash-register.index') }}" class="inline-flex items-center gap-2 px-4 py-2 text-sm text-slate-600 hover:text-slate-900 font-medium">
            <i class="bi bi-arrow-left"></i>
            <span>Volver</span>
        </a>
    </div>

    {{-- Cajas abiertas ahora --}}
    <div class="bg-white rounded-lg border border-emerald-200 overflow-hidden">
        <div class="bg-emerald-50 px-6 py-3 border-b border-emerald-200 flex items-center gap-2">
            <i class="bi bi-unlock-fill text-emerald-600"></i>
            <h2 class="font-semibold text-emerald-900">Cajas abiertas ahora ({{ $openRegisters->count() }})</h2>
        </div>
        @if($openRegisters->count() > 0)
            <div class="divide-y divide-slate-200">
                @foreach($openRegisters as $register)
                    <div class="px-6 py-3 flex items-center justify-between hover:bg-slate-50">
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-slate-900">{{ $register->user->name }}
                                <span class="text-slate-400 font-normal">· {{ $register->branch->name }}</span>
                            </p>
                            <p class="text-xs text-slate-500">Abierta: {{ $register->opened_at->format('d/m/Y H:i') }}</p>
                        </div>
                        <div class="flex items-center gap-4 flex-shrink-0">
                            <span class="text-sm font-semibold text-cyan-600">{{ money($register->total_sales) }}</span>
                            <a href="{{ route('cash-register.show', $register->id) }}" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs bg-cyan-100 hover:bg-cyan-200 text-cyan-700 font-medium rounded transition-colors">
                                <i class="bi bi-eye"></i> Ver
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <p class="px-6 py-4 text-sm text-slate-500 text-center">No hay cajas abiertas en este momento.</p>
        @endif
    </div>

    {{-- Filtros --}}
    <form method="GET" class="bg-white rounded-lg border border-slate-200 p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Sucursal</label>
                <select name="branch_id" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none transition">
                    <option value="">-- Todas las sucursales --</option>
                    @forelse($branches ?? [] as $branch)
                        <option value="{{ $branch->id }}" @selected(request('branch_id') == $branch->id)>{{ $branch->name }}</option>
                    @empty
                    @endforelse
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Usuario</label>
                <select name="user_id" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none transition">
                    <option value="">-- Todos los usuarios --</option>
                    @forelse($users ?? [] as $user)
                        <option value="{{ $user->id }}" @selected(request('user_id') == $user->id)>{{ $user->name }}</option>
                    @empty
                    @endforelse
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Estado</label>
                <select name="status" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none transition">
                    <option value="">-- Todos los estados --</option>
                    <option value="abierta" @selected(request('status') === 'abierta')>Abierta</option>
                    <option value="cerrada" @selected(request('status') === 'cerrada')>Cerrada</option>
                </select>
            </div>

            <div class="flex items-end gap-2">
                <button type="submit" class="flex-1 px-4 py-2 bg-cyan-600 hover:bg-cyan-700 text-white font-medium rounded-lg transition-colors">
                    <i class="bi bi-funnel mr-2"></i>Filtrar
                </button>
                <a href="{{ route('cash-registers.history') }}" class="flex-1 px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-medium rounded-lg transition-colors text-center">
                    <i class="bi bi-arrow-clockwise mr-2"></i>Limpiar
                </a>
            </div>
        </div>
    </form>

    {{-- Tabla de Historial --}}
    <div class="bg-white rounded-lg border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-sm font-medium text-slate-700">Fecha</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-slate-700">Sucursal</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-slate-700">Usuario</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-slate-700">Inicial</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-slate-700">Ventas</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-slate-700">Ganancia</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-slate-700">Esperado</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-slate-700">Contado</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-slate-700">Diferencia</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-slate-700">Estado</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-slate-700">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($registers as $register)
                        @php
                            $service = new \App\Services\CashRegisterService();
                            $stats = $service->getCashRegisterStats($register);
                        @endphp
                    <tr class="hover:bg-slate-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm font-medium">{{ $register->opened_at->format('d/m/Y') }}</span>
                            <p class="text-xs text-slate-500">{{ $register->opened_at->format('H:i') }}</p>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm">{{ $register->branch->name }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm">{{ $register->user->name }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="font-medium">{{ money($stats['opening_amount']) }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-cyan-600 font-medium">{{ money($stats['total_sales']) }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-emerald-600 font-medium">{{ money($stats['total_profit']) }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="font-medium">{{ money($stats['expected_amount']) }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="font-medium">
                                @if($register->closed_at)
                                    {{ money($stats['closing_amount']) }}
                                @else
                                    —
                                @endif
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($register->closed_at)
                                <span class="font-medium @if($stats['difference'] > 0) text-emerald-600 @elseif($stats['difference'] < 0) text-red-600 @else text-slate-600 @endif">
                                    @if($stats['difference'] > 0) +@endif{{ money($stats['difference']) }}
                                </span>
                            @else
                                —
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                @if($register->status === 'abierta') bg-emerald-100 text-emerald-800
                                @else bg-slate-100 text-slate-800
                                @endif">
                                @if($register->status === 'abierta')
                                    <i class="bi bi-check-circle mr-1"></i> Abierta
                                @else
                                    <i class="bi bi-lock-fill mr-1"></i> Cerrada
                                @endif
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right">
                            <div class="inline-flex items-center gap-2">
                                <a href="{{ route('cash-register.show', $register->id) }}" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs bg-cyan-100 hover:bg-cyan-200 text-cyan-700 font-medium rounded transition-colors" title="Ver detalle">
                                    <i class="bi bi-eye"></i> Ver
                                </a>
                                @can('reopen', $register)
                                <button type="button" onclick="reopenRegister({{ $register->id }})"
                                    class="inline-flex items-center gap-1 px-3 py-1.5 text-xs bg-amber-100 hover:bg-amber-200 text-amber-700 font-medium rounded transition-colors" title="Reabrir caja">
                                    <i class="bi bi-unlock"></i> Reabrir
                                </button>
                                @endcan
                                @if(auth()->user()->hasRole('Admin') && $register->sales_count === 0)
                                <button type="button" onclick="deleteRegister({{ $register->id }})"
                                    class="inline-flex items-center gap-1 px-3 py-1.5 text-xs bg-red-100 hover:bg-red-200 text-red-700 font-medium rounded transition-colors" title="Eliminar caja">
                                    <i class="bi bi-trash"></i> Eliminar
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="11" class="px-6 py-8 text-center text-slate-500">
                            <i class="bi bi-inbox text-3xl mb-2 block"></i>
                            No hay cajas registradas
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Paginación --}}
    @if($registers instanceof \Illuminate\Pagination\Paginator)
    <div class="flex items-center justify-center">
        {{ $registers->links() }}
    </div>
    @endif
</div>

<form id="reopenForm" method="POST" class="hidden">@csrf</form>
@endsection
@section('scripts')
<script>
    function reopenRegister(id) {
        ConfirmModal.show({
            title: 'Reabrir caja',
            message: 'Se limpiarán los datos del cierre y la caja volverá a estado "abierta". El cajero podría quedar con más de una caja abierta a la vez.',
            confirmText: 'Reabrir',
            danger: false,
            onConfirm: () => {
                const form = document.getElementById('reopenForm');
                form.action = `{{ url('cash-register') }}/${id}/reopen`;
                form.submit();
            },
        });
    }

    function deleteRegister(id) {
        ConfirmModal.confirmDelete({
            action: `{{ url('cash-register') }}/${id}`,
            title: 'Eliminar caja',
            message: 'Se eliminará la caja y sus movimientos y gastos. Esta acción no se puede deshacer.',
            confirmText: 'Eliminar caja',
        });
    }
</script>
@endsection
