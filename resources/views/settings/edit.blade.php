@extends('layouts.app')
@section('title', 'Configuración')
@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    @include('components.alerts')
    <div>
        <h1 class="text-xl font-semibold text-slate-900">Configuración del Sitio</h1>
        <p class="text-sm text-slate-500 mt-1">Nombre, marca y datos que se usan en toda la aplicación y en el ticket de venta.</p>
    </div>

    <form method="POST" action="{{ route('settings.update') }}" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PUT')

        {{-- Identidad --}}
        <div class="bg-white rounded-lg border border-slate-200 p-6 space-y-5">
            <h2 class="text-sm font-semibold text-slate-900">Identidad</h2>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Nombre del sitio <span class="text-red-500">*</span></label>
                <input type="text" name="site_name" value="{{ old('site_name', $settings['site_name']) }}" required
                       class="w-full max-w-md px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none transition @error('site_name') border-red-500 @enderror">
                @error('site_name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                <p class="mt-1 text-xs text-slate-500">Aparece en la pestaña del navegador, el sidebar, el login y el ticket.</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Color primario</label>
                <div class="flex items-center gap-3">
                    <input type="color" id="primaryColorPicker" value="{{ old('primary_color', $settings['primary_color'] ?? '#0891b2') }}"
                           class="w-12 h-10 border border-slate-300 rounded-lg cursor-pointer">
                    <input type="text" id="primaryColorText" name="primary_color" value="{{ old('primary_color', $settings['primary_color']) }}"
                           placeholder="#0891b2" maxlength="7"
                           class="w-32 px-3 py-2 text-sm font-mono border border-slate-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none transition uppercase @error('primary_color') border-red-500 @enderror">
                    <button type="button" id="resetColorBtn" class="text-sm text-slate-500 hover:text-slate-700 underline">Restablecer (cyan)</button>
                </div>
                @error('primary_color')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                <p class="mt-1 text-xs text-slate-500">Repinta botones, enlaces y acentos en toda la app — sin recargar nada más que este color.</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Logo</label>
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 rounded-xl border border-slate-200 flex items-center justify-center overflow-hidden bg-slate-50 flex-shrink-0">
                        @if($logoUrl)
                        <img src="{{ $logoUrl }}" alt="Logo actual" class="w-full h-full object-cover">
                        @else
                        <i class="bi bi-shop text-slate-300 text-2xl"></i>
                        @endif
                    </div>
                    <div class="flex-1 space-y-2">
                        <input type="file" name="logo" accept=".png,.jpg,.jpeg,.webp,.svg"
                               class="w-full text-sm text-slate-600 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-slate-100 file:text-slate-700 file:text-sm file:font-medium hover:file:bg-slate-200 @error('logo') border-red-500 @enderror">
                        @error('logo')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
                        @if($logoUrl)
                        <label class="flex items-center gap-2 text-sm text-slate-600 cursor-pointer w-fit">
                            <input type="checkbox" name="remove_logo" value="1" class="w-4 h-4 text-red-600 border-slate-300 rounded focus:ring-2 focus:ring-red-500">
                            Quitar el logo actual
                        </label>
                        @endif
                    </div>
                </div>
                <p class="mt-2 text-xs text-slate-500">Reemplaza el ícono de tienda del sidebar y del login. PNG, JPG, WEBP o SVG, máx. 2MB.</p>
            </div>
        </div>

        {{-- Datos del negocio (ticket) --}}
        <div class="bg-white rounded-lg border border-slate-200 p-6 space-y-5">
            <div>
                <h2 class="text-sm font-semibold text-slate-900">Datos del negocio</h2>
                <p class="text-xs text-slate-500 mt-1">Se imprimen en el ticket de venta. Deja en blanco lo que no quieras mostrar.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Nombre comercial</label>
                    <input type="text" name="business_name" value="{{ old('business_name', $settings['business_name']) }}" placeholder="{{ $settings['site_name'] }}"
                           class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none transition @error('business_name') border-red-500 @enderror">
                    @error('business_name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    <p class="mt-1 text-xs text-slate-500">Si se deja vacío, el ticket usa el nombre del sitio.</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Teléfono</label>
                    <input type="text" name="business_phone" value="{{ old('business_phone', $settings['business_phone']) }}"
                           class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none transition @error('business_phone') border-red-500 @enderror">
                    @error('business_phone')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-slate-700 mb-2">Dirección</label>
                    <input type="text" name="business_address" value="{{ old('business_address', $settings['business_address']) }}"
                           class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none transition @error('business_address') border-red-500 @enderror">
                    @error('business_address')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">RFC</label>
                    <input type="text" name="business_tax_id" value="{{ old('business_tax_id', $settings['business_tax_id']) }}"
                           class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none transition @error('business_tax_id') border-red-500 @enderror">
                    @error('business_tax_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Símbolo de moneda</label>
                    <input type="text" name="currency_symbol" value="{{ old('currency_symbol', $settings['currency_symbol']) }}" maxlength="5"
                           class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none transition @error('currency_symbol') border-red-500 @enderror">
                    @error('currency_symbol')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-slate-700 mb-2">Mensaje de pie del ticket</label>
                    <input type="text" name="ticket_footer" value="{{ old('ticket_footer', $settings['ticket_footer']) }}" maxlength="255"
                           class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none transition @error('ticket_footer') border-red-500 @enderror">
                    @error('ticket_footer')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <button type="submit" class="inline-flex items-center gap-2 px-4 py-2.5 bg-cyan-600 hover:bg-cyan-700 text-white text-sm font-medium rounded-lg transition-colors">
                <i class="bi bi-check-lg"></i><span>Guardar cambios</span>
            </button>
        </div>
    </form>
</div>
@endsection
@section('scripts')
<script>
const picker = document.getElementById('primaryColorPicker');
const text = document.getElementById('primaryColorText');
const resetBtn = document.getElementById('resetColorBtn');

picker.addEventListener('input', () => { text.value = picker.value; });
text.addEventListener('input', () => {
    if (/^#[0-9a-fA-F]{6}$/.test(text.value)) picker.value = text.value;
});
// "Restablecer" deja el campo vacio: sin valor guardado, la app vuelve al
// cyan de fabrica de Tailwind en vez de fijar un hex especifico.
resetBtn.addEventListener('click', () => {
    text.value = '';
    picker.value = '#0891b2';
});
</script>
@endsection
