@extends('layouts.app')
@section('title', 'Abrir Caja')
@section('content')
<div class="max-w-xl mx-auto space-y-6">
    @include('components.alerts')

    <div class="flex items-center gap-3">
        <a href="{{ route('cash-register.index') }}" class="p-2 hover:bg-slate-100 rounded-lg transition-colors">
            <i class="bi bi-arrow-left text-slate-600"></i>
        </a>
        <div>
            <h1 class="text-xl font-semibold text-slate-900">Abrir Caja</h1>
            <p class="text-sm text-slate-500 mt-1">Inicia un turno de ventas</p>
        </div>
    </div>

    <form method="POST" action="{{ route('cash-register.store-open') }}" class="bg-white rounded-lg border border-slate-200">
        @csrf

        <div class="p-6 space-y-5">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">
                    Sucursal <span class="text-red-500">*</span>
                </label>
                <select name="branch_id"
                        required
                        autofocus
                        class="w-full px-4 py-3 border border-slate-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none transition @error('branch_id') border-red-500 @enderror">
                    <option value="">-- Selecciona una sucursal --</option>
                    @foreach ($branches as $branch)
                        <option value="{{ $branch->id }}" @selected(old('branch_id') == $branch->id)>
                            {{ $branch->name }}
                        </option>
                    @endforeach
                </select>
                @error('branch_id')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">
                    Monto Inicial en Caja <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-slate-500">$</span>
                    <input type="number"
                           name="opening_amount"
                           value="{{ old('opening_amount', '0.00') }}"
                           step="0.01"
                           min="0"
                           required
                           class="w-full pl-8 pr-4 py-3 text-lg border border-slate-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none transition @error('opening_amount') border-red-500 @enderror">
                </div>
                <p class="mt-1 text-xs text-slate-500">Ingresa el dinero que hay en la caja al iniciar</p>
                @error('opening_amount')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">
                    Observaciones (Opcional)
                </label>
                <textarea name="opening_notes"
                          maxlength="500"
                          class="w-full px-4 py-3 border border-slate-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none transition @error('opening_notes') border-red-500 @enderror"
                          rows="2"
                          placeholder="Ej: Caja de reserva, cambios faltantes, etc.">{{ old('opening_notes') }}</textarea>
                @error('opening_notes')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="flex items-center gap-3 px-6 py-4 bg-slate-50 border-t border-slate-200 rounded-b-lg">
            <button type="submit" class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-lg transition-colors">
                <i class="bi bi-check-lg"></i>
                <span>Abrir Caja</span>
            </button>
            <a href="{{ route('cash-register.index') }}" class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-medium rounded-lg transition-colors">
                <span>Cancelar</span>
            </a>
        </div>
    </form>
</div>
@endsection
