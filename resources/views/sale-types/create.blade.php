@extends('layouts.app')
@section('title', 'Nuevo Tipo de Venta')
@section('content')
<div class="max-w-2xl mx-auto space-y-6">
    @include('components.alerts')
    <div class="flex items-center gap-3"><a href="{{ route('sale-types.index') }}" class="p-2 hover:bg-slate-100 rounded-lg transition-colors"><i class="bi bi-arrow-left text-slate-600"></i></a><div><h1 class="text-xl font-semibold text-slate-900">Nuevo Tipo de Venta</h1><p class="text-sm text-slate-500 mt-1">Crea un nuevo tipo de venta</p></div></div>
    <form method="POST" action="{{ route('sale-types.store') }}" class="bg-white rounded-lg border border-slate-200">
        @csrf
        <div class="p-6 space-y-5">
            <div><label class="block text-sm font-medium text-slate-700 mb-2">Nombre <span class="text-red-500">*</span></label><input type="text" name="name" value="{{ old('name') }}" required autofocus class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none transition @error('name') border-red-500 @enderror" placeholder="Ej: Pieza">@error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror</div>
            <div><label class="block text-sm font-medium text-slate-700 mb-2">Unidad Base <span class="text-red-500">*</span></label><input type="text" name="base_unit" value="{{ old('base_unit', 'pza') }}" required class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none transition @error('base_unit') border-red-500 @enderror" placeholder="Ej: pza, kg, lt">@error('base_unit')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror<p class="mt-1 text-xs text-slate-500">Unidad que se asignará automáticamente a los productos con este tipo de venta</p></div>
            <div><label class="flex items-center gap-3 cursor-pointer"><input type="checkbox" name="allows_decimals" value="1" {{ old('allows_decimals') ? 'checked' : '' }} class="w-4 h-4 text-cyan-600 border-slate-300 rounded focus:ring-2 focus:ring-cyan-500 transition"><span class="text-sm font-medium text-slate-700">Permite decimales</span></label><p class="mt-1 text-xs text-slate-500 ml-7">Habilitar para productos que se venden por peso o volumen (ej: 1.5 kg)</p></div>
            <div><label class="flex items-center gap-3 cursor-pointer"><input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }} class="w-4 h-4 text-cyan-600 border-slate-300 rounded focus:ring-2 focus:ring-cyan-500 transition"><span class="text-sm font-medium text-slate-700">Tipo activo</span></label></div>
        </div>
        <div class="flex items-center gap-3 px-6 py-4 bg-slate-50 border-t border-slate-200 rounded-b-lg"><button type="submit" class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-cyan-600 hover:bg-cyan-700 text-white text-sm font-medium rounded-lg transition-colors"><i class="bi bi-check-lg"></i><span>Crear</span></button><a href="{{ route('sale-types.index') }}" class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-medium rounded-lg transition-colors"><span>Cancelar</span></a></div>
    </form>
</div>
@endsection
