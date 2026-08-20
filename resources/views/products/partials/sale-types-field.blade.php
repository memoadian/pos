{{--
    Selector de tipos de venta del producto.

    Un producto puede venderse de varias formas (pza, kg, caja): se marcan
    todos los tipos con checkbox y uno se elige como principal. El principal
    define la unidad base del inventario y usa los precios del bloque
    "Precios" de arriba; cada tipo adicional trae sus propios precios y su
    factor de conversion hacia esa unidad base.

    Espera: $saleTypes (tipos activos) y opcionalmente $product (en edicion).
--}}
@php
    $editing = isset($product) && $product->exists;
    $extras = $editing ? $product->productSaleTypes->keyBy('sale_type_id') : collect();

    $defaultId = (int) old('default_sale_type_id', $editing ? $product->sale_type_id : 0);

    $checkedIds = old('sale_type_ids');
    if ($checkedIds === null) {
        $checkedIds = $editing
            ? $extras->keys()->push($product->sale_type_id)->all()
            : ($defaultId ? [$defaultId] : []);
    }
    $checkedIds = collect($checkedIds)->map(fn ($id) => (int) $id)->all();
@endphp
<div data-sale-types-field>
    <label class="block text-sm font-medium text-slate-700 mb-2">Tipos de Venta <span class="text-red-500">*</span></label>
    <p class="text-xs text-slate-500 mb-3">Marca todas las formas en que se vende este producto. La marcada como <strong>Principal</strong> define la unidad del inventario y usa los precios de arriba; cada tipo adicional lleva sus propios precios.</p>
    @error('sale_type_ids')<p class="mb-2 text-sm text-red-600">{{ $message }}</p>@enderror
    @error('default_sale_type_id')<p class="mb-2 text-sm text-red-600">{{ $message }}</p>@enderror
    <div class="space-y-2">
        @foreach($saleTypes as $st)
        @php
            $extra = $extras[$st->id] ?? null;
            $isChecked = in_array($st->id, $checkedIds, true);
            $isDefault = $defaultId === (int) $st->id;
            $hasErrors = $errors->hasAny([
                "sale_types.{$st->id}.conversion_factor",
                "sale_types.{$st->id}.price_retail",
                "sale_types.{$st->id}.price_wholesale",
                "sale_types.{$st->id}.price_super_wholesale",
                "sale_types.{$st->id}.min_wholesale_qty",
                "sale_types.{$st->id}.min_super_wholesale_qty",
            ]);
        @endphp
        <div class="border border-slate-200 rounded-lg overflow-hidden">
            <div class="flex items-center justify-between gap-3 px-4 py-3">
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="checkbox" name="sale_type_ids[]" value="{{ $st->id }}" data-sale-type-check="{{ $st->id }}" {{ $isChecked ? 'checked' : '' }} class="w-4 h-4 text-cyan-600 border-slate-300 rounded focus:ring-2 focus:ring-cyan-500 transition">
                    <span class="text-sm font-medium text-slate-700">{{ $st->name }} <span class="text-slate-400 font-normal">({{ $st->base_unit }})</span></span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="radio" name="default_sale_type_id" value="{{ $st->id }}" data-sale-type-default="{{ $st->id }}" data-base-unit="{{ $st->base_unit }}" data-name="{{ $st->name }}" {{ $isDefault ? 'checked' : '' }} class="w-4 h-4 text-cyan-600 border-slate-300 focus:ring-2 focus:ring-cyan-500 transition">
                    <span class="text-xs text-slate-500">Principal</span>
                </label>
            </div>
            <div data-sale-type-panel="{{ $st->id }}" class="{{ $isChecked && ! $isDefault ? '' : 'hidden' }} border-t border-slate-200 bg-slate-50 px-4 py-4 space-y-4">
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1.5">Equivalencia <span class="text-red-500">*</span></label>
                    <div class="flex items-center gap-2 flex-wrap text-sm text-slate-600">
                        <span>1 {{ $st->base_unit }} =</span>
                        <input type="number" name="sale_types[{{ $st->id }}][conversion_factor]" value="{{ old("sale_types.{$st->id}.conversion_factor", $extra?->conversion_factor ?? 1) }}" step="0.0001" min="0.0001" onfocus="this.select()" class="w-28 px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none transition">
                        <span data-base-unit-label>{{ $product->unit_base ?? 'unidad base' }}</span>
                        <span class="text-xs text-slate-400">(lo que se descuenta del inventario)</span>
                    </div>
                    @error("sale_types.{$st->id}.conversion_factor")<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div><label class="block text-xs font-medium text-slate-600 mb-1.5">Menudeo <span class="text-red-500">*</span></label><input type="number" name="sale_types[{{ $st->id }}][price_retail]" value="{{ old("sale_types.{$st->id}.price_retail", $extra?->price_retail ?? '0.00') }}" step="0.01" min="0" onfocus="this.select()" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none transition">@error("sale_types.{$st->id}.price_retail")<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror</div>
                    <div><label class="block text-xs font-medium text-slate-600 mb-1.5">Mayoreo <span class="text-red-500">*</span></label><input type="number" name="sale_types[{{ $st->id }}][price_wholesale]" value="{{ old("sale_types.{$st->id}.price_wholesale", $extra?->price_wholesale ?? '0.00') }}" step="0.01" min="0" onfocus="this.select()" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none transition">@error("sale_types.{$st->id}.price_wholesale")<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror</div>
                    <div><label class="block text-xs font-medium text-slate-600 mb-1.5">Super Mayoreo <span class="text-red-500">*</span></label><input type="number" name="sale_types[{{ $st->id }}][price_super_wholesale]" value="{{ old("sale_types.{$st->id}.price_super_wholesale", $extra?->price_super_wholesale ?? '0.00') }}" step="0.01" min="0" onfocus="this.select()" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none transition">@error("sale_types.{$st->id}.price_super_wholesale")<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror</div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div><label class="block text-xs font-medium text-slate-600 mb-1.5">Cantidad mín. Mayoreo <span class="text-slate-400 font-normal">(opcional)</span></label><input type="number" name="sale_types[{{ $st->id }}][min_wholesale_qty]" value="{{ old("sale_types.{$st->id}.min_wholesale_qty", $extra?->min_wholesale_qty) }}" step="1" min="1" placeholder="Hereda la del producto" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none transition">@error("sale_types.{$st->id}.min_wholesale_qty")<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror</div>
                    <div><label class="block text-xs font-medium text-slate-600 mb-1.5">Cantidad mín. Super Mayoreo <span class="text-slate-400 font-normal">(opcional)</span></label><input type="number" name="sale_types[{{ $st->id }}][min_super_wholesale_qty]" value="{{ old("sale_types.{$st->id}.min_super_wholesale_qty", $extra?->min_super_wholesale_qty) }}" step="1" min="1" placeholder="Hereda la del producto" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none transition">@error("sale_types.{$st->id}.min_super_wholesale_qty")<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror</div>
                </div>
                @if($hasErrors)<p class="text-xs text-red-600">Revisa los datos de este tipo de venta</p>@endif
            </div>
        </div>
        @endforeach
    </div>
    @if($editing)
    <p class="mt-3 text-xs text-amber-600 hidden" data-default-warning><i class="bi bi-exclamation-triangle mr-1"></i>Cambiar el tipo principal reinterpreta el stock existente, que está guardado en la unidad del tipo principal anterior.</p>
    @endif
</div>
<script>
(function () {
    const field = document.currentScript.previousElementSibling;
    const originalDefault = field.querySelector('[data-sale-type-default]:checked')?.value || null;

    function syncPanels() {
        const defaultId = field.querySelector('[data-sale-type-default]:checked')?.value || null;

        field.querySelectorAll('[data-sale-type-check]').forEach(check => {
            const id = check.dataset.saleTypeCheck;
            const panel = field.querySelector(`[data-sale-type-panel="${id}"]`);
            // El tipo principal no muestra panel: sus precios son los de arriba
            panel.classList.toggle('hidden', !check.checked || id === defaultId);
            panel.querySelectorAll('input').forEach(input => {
                input.disabled = panel.classList.contains('hidden');
            });
        });

        const defaultRadio = defaultId ? field.querySelector(`[data-sale-type-default="${defaultId}"]`) : null;
        const unit = defaultRadio?.dataset.baseUnit || 'unidad base';
        field.querySelectorAll('[data-base-unit-label]').forEach(el => { el.textContent = unit; });

        const label = document.querySelector('[data-default-sale-type-label]');
        if (label) label.textContent = defaultRadio ? `${defaultRadio.dataset.name} (${unit})` : '—';

        const warning = field.querySelector('[data-default-warning]');
        if (warning) warning.classList.toggle('hidden', !originalDefault || defaultId === originalDefault);
    }

    field.addEventListener('change', function (event) {
        const radio = event.target.closest('[data-sale-type-default]');
        if (radio) {
            // Elegir un tipo como principal implica venderlo
            field.querySelector(`[data-sale-type-check="${radio.value}"]`).checked = true;
        }

        const check = event.target.closest('[data-sale-type-check]');
        if (check && !check.checked) {
            const radioOfCheck = field.querySelector(`[data-sale-type-default="${check.dataset.saleTypeCheck}"]`);
            if (radioOfCheck?.checked) {
                // Al desmarcar el principal, el primero que quede toma su lugar
                radioOfCheck.checked = false;
                const fallback = field.querySelector('[data-sale-type-check]:checked');
                if (fallback) {
                    field.querySelector(`[data-sale-type-default="${fallback.dataset.saleTypeCheck}"]`).checked = true;
                }
            }
        }

        // Sin principal elegido (alta nueva), el primer tipo marcado lo toma:
        // asi el caso comun de un solo tipo no obliga a un clic extra.
        if (!field.querySelector('[data-sale-type-default]:checked')) {
            const first = field.querySelector('[data-sale-type-check]:checked');
            if (first) {
                field.querySelector(`[data-sale-type-default="${first.dataset.saleTypeCheck}"]`).checked = true;
            }
        }

        syncPanels();
    });

    syncPanels();
})();
</script>
