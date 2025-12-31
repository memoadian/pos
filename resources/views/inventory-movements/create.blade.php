@extends('layouts.app')
@section('title', 'Nuevo Movimiento')
@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    @include('components.alerts')
    <div class="flex items-center gap-3">
        <a href="{{ route('inventory-movements.index') }}" class="p-2 hover:bg-slate-100 rounded-lg transition-colors"><i class="bi bi-arrow-left text-slate-600"></i></a>
        <div><h1 class="text-xl font-semibold text-slate-900">Nuevo Movimiento de Inventario</h1><p class="text-sm text-slate-500 mt-1">Registra entrada, salida o ajuste de stock</p></div>
    </div>
    <form method="POST" action="{{ route('inventory-movements.store') }}" class="bg-white rounded-lg border border-slate-200">
        @csrf
        <div class="p-6 space-y-5">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div><label class="block text-sm font-medium text-slate-700 mb-2">Sucursal <span class="text-red-500">*</span></label><select name="branch_id" required class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none transition"><option value="">Seleccionar...</option>@foreach($branches as $b)<option value="{{ $b->id }}" {{ old('branch_id') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>@endforeach</select>@error('branch_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror</div>
                <div><label class="block text-sm font-medium text-slate-700 mb-2">Producto <span class="text-red-500">*</span></label><select name="product_id" required class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none transition"><option value="">Seleccionar...</option>@foreach($products as $p)<option value="{{ $p->id }}" {{ old('product_id') == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>@endforeach</select>@error('product_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror</div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div><label class="block text-sm font-medium text-slate-700 mb-2">Tipo <span class="text-red-500">*</span></label><select name="type" required class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none transition"><option value="">Seleccionar...</option><option value="IN" {{ old('type') == 'IN' ? 'selected' : '' }}>Entrada</option><option value="OUT" {{ old('type') == 'OUT' ? 'selected' : '' }}>Salida</option><option value="ADJUST" {{ old('type') == 'ADJUST' ? 'selected' : '' }}>Ajuste</option></select></div>
                <div><label class="block text-sm font-medium text-slate-700 mb-2">Cantidad <span class="text-red-500">*</span></label><input type="number" name="quantity" value="{{ old('quantity') }}" step="0.01" min="0.01" required class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none transition"></div>
            </div>
            <div><label class="block text-sm font-medium text-slate-700 mb-2">Motivo <span class="text-red-500">*</span></label><textarea name="reason" rows="3" required class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none transition">{{ old('reason') }}</textarea>@error('reason')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror</div>
        </div>
        <div class="flex items-center gap-3 px-6 py-4 bg-slate-50 border-t border-slate-200 rounded-b-lg">
            <button type="submit" class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-cyan-600 hover:bg-cyan-700 text-white text-sm font-medium rounded-lg transition-colors"><i class="bi bi-check-lg"></i><span>Registrar Movimiento</span></button>
            <a href="{{ route('inventory-movements.index') }}" class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-medium rounded-lg transition-colors"><span>Cancelar</span></a>
        </div>
    </form>
</div>
@endsection
