@extends('layouts.app')

@section('title', 'Nueva Sucursal')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">
    <!-- Alerts -->
    @include('components.alerts')

    <!-- Header -->
    <div class="flex items-center gap-3">
        <a href="{{ route('branches.index') }}"
           class="p-2 hover:bg-slate-100 rounded-lg transition-colors">
            <i class="bi bi-arrow-left text-slate-600"></i>
        </a>
        <div>
            <h1 class="text-xl font-semibold text-slate-900">Nueva Sucursal</h1>
            <p class="text-sm text-slate-500 mt-1">Crea una nueva sucursal en el sistema</p>
        </div>
    </div>

    <!-- Form -->
    <form method="POST" action="{{ route('branches.store') }}" class="bg-white rounded-lg border border-slate-200">
        @csrf

        <div class="p-6 space-y-5">
            <!-- Nombre -->
            <div>
                <label for="name" class="block text-sm font-medium text-slate-700 mb-2">
                    Nombre de la Sucursal <span class="text-red-500">*</span>
                </label>
                <input type="text"
                       id="name"
                       name="name"
                       value="{{ old('name') }}"
                       required
                       autofocus
                       class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none transition @error('name') border-red-500 @enderror"
                       placeholder="Ej: Sucursal Centro">
                @error('name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Dirección -->
            <div>
                <label for="address" class="block text-sm font-medium text-slate-700 mb-2">
                    Dirección
                </label>
                <textarea
                    id="address"
                    name="address"
                    rows="3"
                    class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none transition @error('address') border-red-500 @enderror"
                    placeholder="Ej: Av. Principal #123, Col. Centro">{{ old('address') }}</textarea>
                @error('address')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Estado Activo -->
            <div>
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="checkbox"
                           name="is_active"
                           value="1"
                           {{ old('is_active', true) ? 'checked' : '' }}
                           class="w-4 h-4 text-cyan-600 border-slate-300 rounded focus:ring-2 focus:ring-cyan-500 transition">
                    <div>
                        <span class="text-sm font-medium text-slate-700">Sucursal activa</span>
                        <p class="text-xs text-slate-500">Las sucursales inactivas no permiten ventas ni movimientos de inventario</p>
                    </div>
                </label>
            </div>
        </div>

        <div class="flex items-center gap-3 px-6 py-4 bg-slate-50 border-t border-slate-200 rounded-b-lg">
            <button type="submit"
                    class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-cyan-600 hover:bg-cyan-700 text-white text-sm font-medium rounded-lg transition-colors">
                <i class="bi bi-check-lg"></i>
                <span>Crear Sucursal</span>
            </button>
            <a href="{{ route('branches.index') }}"
               class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-medium rounded-lg transition-colors">
                <span>Cancelar</span>
            </a>
        </div>
    </form>
</div>
@endsection
