@extends('layouts.app')
@section('title', 'Editar Producto')
@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    @include('components.alerts')
    <div class="flex items-center gap-3">
        <a href="{{ route('products.index') }}" class="p-2 hover:bg-slate-100 rounded-lg transition-colors"><i class="bi bi-arrow-left text-slate-600"></i></a>
        <div><h1 class="text-xl font-semibold text-slate-900">Editar Producto</h1><p class="text-sm text-slate-500 mt-1">Actualiza la información del producto</p></div>
    </div>
    <form method="POST" action="{{ route('products.update', $product) }}" class="bg-white rounded-lg border border-slate-200">
        @csrf
        @method('PUT')
        <div class="p-6 space-y-5">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div><label class="block text-sm font-medium text-slate-700 mb-2">Departamento <span class="text-red-500">*</span></label><select name="department_id" required class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none transition"><option value="">Seleccionar...</option>@foreach($departments as $dept)<option value="{{ $dept->id }}" {{ old('department_id', $product->department_id) == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>@endforeach</select></div>
                <div><label class="block text-sm font-medium text-slate-700 mb-2">Código de Barras</label><input type="text" name="barcode" value="{{ old('barcode', $product->barcode) }}" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none transition"></div>
            </div>
            <div><label class="block text-sm font-medium text-slate-700 mb-2">Nombre <span class="text-red-500">*</span></label><input type="text" name="name" value="{{ old('name', $product->name) }}" required class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none transition"></div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div><label class="block text-sm font-medium text-slate-700 mb-2">Tipo Venta <span class="text-red-500">*</span></label><select name="sale_type" required class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none transition"><option value="pieza" {{ old('sale_type', $product->sale_type) == 'pieza' ? 'selected' : '' }}>Pieza</option><option value="granel" {{ old('sale_type', $product->sale_type) == 'granel' ? 'selected' : '' }}>Granel</option><option value="peso" {{ old('sale_type', $product->sale_type) == 'peso' ? 'selected' : '' }}>Peso</option></select></div>
                <div><label class="block text-sm font-medium text-slate-700 mb-2">Unidad Base <span class="text-red-500">*</span></label><input type="text" name="unit_base" value="{{ old('unit_base', $product->unit_base) }}" required class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none transition"></div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div><label class="block text-sm font-medium text-slate-700 mb-2">Precio Menudeo <span class="text-red-500">*</span></label><input type="number" name="price_retail" value="{{ old('price_retail', $product->price_retail) }}" step="0.01" min="0" required class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none transition"></div>
                <div><label class="block text-sm font-medium text-slate-700 mb-2">Precio Mayoreo <span class="text-red-500">*</span></label><input type="number" name="price_wholesale" value="{{ old('price_wholesale', $product->price_wholesale) }}" step="0.01" min="0" required class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none transition"></div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div><label class="block text-sm font-medium text-slate-700 mb-2">Precio Super Mayoreo <span class="text-red-500">*</span></label><input type="number" name="price_super_wholesale" value="{{ old('price_super_wholesale', $product->price_super_wholesale) }}" step="0.01" min="0" required class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none transition"></div>
                <div><label class="block text-sm font-medium text-slate-700 mb-2">Costo <span class="text-red-500">*</span></label><input type="number" name="cost" value="{{ old('cost', $product->cost) }}" step="0.01" min="0" required class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none transition"></div>
            </div>
            <div><label class="flex items-center gap-3 cursor-pointer"><input type="checkbox" name="is_active" value="1" {{ old('is_active', $product->is_active) ? 'checked' : '' }} class="w-4 h-4 text-cyan-600 border-slate-300 rounded focus:ring-2 focus:ring-cyan-500 transition"><span class="text-sm font-medium text-slate-700">Producto activo</span></label></div>
        </div>
        <div class="flex items-center gap-3 px-6 py-4 bg-slate-50 border-t border-slate-200 rounded-b-lg">
            <button type="submit" class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-cyan-600 hover:bg-cyan-700 text-white text-sm font-medium rounded-lg transition-colors"><i class="bi bi-check-lg"></i><span>Actualizar Producto</span></button>
            <a href="{{ route('products.index') }}" class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-medium rounded-lg transition-colors"><span>Cancelar</span></a>
        </div>
    </form>
</div>
@endsection
