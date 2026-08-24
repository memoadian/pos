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
            @include('products.partials.aliases-field', ['aliases' => old('aliases', $product->aliases->pluck('alias')->all())])
            @include('products.partials.sale-types-field')
            <div class="border-t border-slate-200 pt-5">
                <h3 class="text-sm font-medium text-slate-700">Precios del tipo principal <span class="text-xs text-slate-500 font-normal" data-default-sale-type-label>—</span></h3>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div><label class="block text-sm font-medium text-slate-700 mb-2">Precio Menudeo <span class="text-red-500">*</span></label><input type="number" name="price_retail" value="{{ old('price_retail', $product->price_retail) }}" step="0.01" min="0" required onfocus="this.select()" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none transition"></div>
                <div><label class="block text-sm font-medium text-slate-700 mb-2">Precio Mayoreo <span class="text-red-500">*</span></label><input type="number" name="price_wholesale" value="{{ old('price_wholesale', $product->price_wholesale) }}" step="0.01" min="0" required onfocus="this.select()" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none transition"></div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div><label class="block text-sm font-medium text-slate-700 mb-2">Precio Super Mayoreo <span class="text-red-500">*</span></label><input type="number" name="price_super_wholesale" value="{{ old('price_super_wholesale', $product->price_super_wholesale) }}" step="0.01" min="0" required onfocus="this.select()" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none transition"></div>
                <div><label class="block text-sm font-medium text-slate-700 mb-2">Costo <span class="text-red-500">*</span></label><input type="number" name="cost" value="{{ old('cost', $product->cost) }}" step="0.01" min="0" required onfocus="this.select()" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none transition"></div>
            </div>
            <div class="border-t border-slate-200 pt-5">
                <h3 class="text-sm font-medium text-slate-700 mb-3">Control de Inventario <span class="text-xs text-slate-500 font-normal">(opcional)</span></h3>
                <p class="text-xs text-slate-500 mb-4">Define a partir de qué cantidad de stock se considera bajo, para poder filtrarlo en Inventario</p>
                <div><label class="block text-sm font-medium text-slate-700 mb-2">Stock Mínimo</label><input type="number" name="min_stock" value="{{ old('min_stock', $product->min_stock) }}" step="0.01" min="0" placeholder="Ej: 10" onfocus="this.select()" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none transition @error('min_stock') border-red-500 @enderror">@error('min_stock')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror</div>
            </div>
            <div class="border-t border-slate-200 pt-5">
                <h3 class="text-sm font-medium text-slate-700 mb-3">Umbrales de Precio Automático <span class="text-xs text-slate-500 font-normal">(opcional)</span></h3>
                <p class="text-xs text-slate-500 mb-4">Define la cantidad mínima para aplicar automáticamente cada nivel de precio en el POS</p>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div><label class="block text-sm font-medium text-slate-700 mb-2">Cantidad mín. Mayoreo</label><input type="number" name="min_wholesale_qty" value="{{ old('min_wholesale_qty', $product->min_wholesale_qty) }}" step="1" min="1" placeholder="Ej: 10" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none transition">@error('min_wholesale_qty')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror</div>
                    <div><label class="block text-sm font-medium text-slate-700 mb-2">Cantidad mín. Super Mayoreo</label><input type="number" name="min_super_wholesale_qty" value="{{ old('min_super_wholesale_qty', $product->min_super_wholesale_qty) }}" step="1" min="1" placeholder="Ej: 50" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none transition">@error('min_super_wholesale_qty')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror</div>
                </div>
            </div>
            <div><label class="flex items-center gap-3 cursor-pointer"><input type="checkbox" name="is_active" value="1" {{ old('is_active', $product->is_active) ? 'checked' : '' }} class="w-4 h-4 text-cyan-600 border-slate-300 rounded focus:ring-2 focus:ring-cyan-500 transition"><span class="text-sm font-medium text-slate-700">Producto activo</span></label></div>
        </div>
        <div class="flex items-center gap-3 px-6 py-4 bg-slate-50 border-t border-slate-200 rounded-b-lg">
            <button type="submit" class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-cyan-600 hover:bg-cyan-700 text-white text-sm font-medium rounded-lg transition-colors"><i class="bi bi-check-lg"></i><span>Actualizar Producto</span></button>
            <a href="{{ route('products.index') }}" class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-medium rounded-lg transition-colors"><span>Cancelar</span></a>
        </div>
    </form>

    <form method="POST" action="{{ route('products.branch-prices.sync', $product) }}" class="bg-white rounded-lg border border-slate-200">
        @csrf
        @method('PUT')
        <div class="p-6 space-y-5">
            <div>
                <h2 class="text-sm font-medium text-slate-700">Precios por Sucursal <span class="text-xs text-slate-500 font-normal">(opcional)</span></h2>
                <p class="text-xs text-slate-500 mt-1">Define un precio distinto solo donde haga falta, por sucursal y tipo de venta. Un campo vacío hereda el precio general de ese tipo, mostrado como referencia en cada casilla.</p>
            </div>
            @forelse($branches as $branch)
            <div class="border border-slate-200 rounded-lg p-4 space-y-4">
                <h3 class="text-sm font-medium text-slate-900"><i class="bi bi-shop text-slate-400 mr-1"></i>{{ $branch->name }}</h3>
                @foreach($saleTypeOptions as $option)
                @php $override = $branchPrices[$branch->id . '-' . $option['sale_type_id']] ?? null; @endphp
                <div>
                    @if(count($saleTypeOptions) > 1)
                    <p class="text-xs font-medium text-slate-500 mb-2">{{ $option['name'] }} <span class="text-slate-400 font-normal">({{ $option['unit'] }}){{ $option['is_default'] ? ' · principal' : '' }}</span></p>
                    @endif
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        @foreach(['price_retail' => 'Menudeo', 'price_wholesale' => 'Mayoreo', 'price_super_wholesale' => 'Super Mayoreo'] as $field => $label)
                        <div><label class="block text-xs font-medium text-slate-600 mb-1.5">{{ $label }}</label><input type="number" name="prices[{{ $branch->id }}][{{ $option['sale_type_id'] }}][{{ $field }}]" value="{{ old("prices.{$branch->id}.{$option['sale_type_id']}.{$field}", $override?->{$field}) }}" step="0.01" min="0" placeholder="{{ number_format($option[$field], 2) }}" onfocus="this.select()" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none transition"></div>
                        @endforeach
                    </div>
                </div>
                @endforeach
            </div>
            @empty
            <p class="text-sm text-slate-500 py-4 text-center">No hay sucursales activas</p>
            @endforelse
        </div>
        @if($branches->isNotEmpty())
        <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 rounded-b-lg">
            <button type="submit" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-cyan-600 hover:bg-cyan-700 text-white text-sm font-medium rounded-lg transition-colors"><i class="bi bi-check-lg"></i><span>Guardar Precios por Sucursal</span></button>
        </div>
        @endif
    </form>
</div>
@endsection
