{{--
    Captura de alias como chips.

    $aliases  array|Collection de strings ya resueltos por el formulario
--}}
<div>
    <label class="block text-sm font-medium text-slate-700 mb-2">
        Alias <span class="text-xs text-slate-500 font-normal">(opcional)</span>
    </label>
    <p class="text-xs text-slate-500 mb-2">
        Otros nombres con los que el mostrador busca este producto: marca, apodo o abreviatura.
        Escribe uno y presiona <kbd class="px-1 py-0.5 bg-slate-100 border border-slate-300 rounded text-[10px]">Enter</kbd>.
    </p>

    <div id="aliasBox"
         class="flex flex-wrap items-center gap-2 w-full px-3 py-2 border border-slate-300 rounded-lg focus-within:ring-2 focus-within:ring-cyan-500 focus-within:border-cyan-500 transition cursor-text">
        @foreach($aliases as $alias)
        <span class="inline-flex items-center gap-1.5 pl-2.5 pr-1.5 py-1 bg-cyan-50 border border-cyan-200 text-cyan-800 text-sm rounded-full" data-alias-chip>
            <input type="hidden" name="aliases[]" value="{{ $alias }}">
            <span>{{ $alias }}</span>
            <button type="button" class="text-cyan-500 hover:text-cyan-800 transition-colors" data-alias-remove aria-label="Quitar alias">
                <i class="bi bi-x-lg text-[10px]"></i>
            </button>
        </span>
        @endforeach
        <input type="text" id="aliasInput" placeholder="Ej: dogo, chupiral..."
               class="flex-1 min-w-32 py-1 text-sm outline-none bg-transparent" autocomplete="off">
    </div>
    @error('aliases')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    @error('aliases.*')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
</div>

<script>
(function () {
    const box = document.getElementById('aliasBox');
    const input = document.getElementById('aliasInput');

    const values = () => [...box.querySelectorAll('[data-alias-chip] input')].map(i => i.value.toLowerCase());

    function addAlias(raw) {
        const alias = raw.trim();
        // El duplicado se descarta aqui tambien: el unique de la tabla es lo
        // ultimo que atrapa, pero avisar tarde seria peor experiencia.
        if (!alias || values().includes(alias.toLowerCase())) return;

        const chip = document.createElement('span');
        chip.className = 'inline-flex items-center gap-1.5 pl-2.5 pr-1.5 py-1 bg-cyan-50 border border-cyan-200 text-cyan-800 text-sm rounded-full';
        chip.setAttribute('data-alias-chip', '');

        const hidden = document.createElement('input');
        hidden.type = 'hidden';
        hidden.name = 'aliases[]';
        hidden.value = alias;

        const label = document.createElement('span');
        label.textContent = alias;

        const remove = document.createElement('button');
        remove.type = 'button';
        remove.className = 'text-cyan-500 hover:text-cyan-800 transition-colors';
        remove.setAttribute('data-alias-remove', '');
        remove.setAttribute('aria-label', 'Quitar alias');
        remove.innerHTML = '<i class="bi bi-x-lg text-[10px]"></i>';

        chip.append(hidden, label, remove);
        box.insertBefore(chip, input);
    }

    input.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' || e.key === ',') {
            // Enter dentro de un form manda el form: aqui solo cierra el chip.
            e.preventDefault();
            addAlias(input.value);
            input.value = '';
            return;
        }

        if (e.key === 'Backspace' && input.value === '') {
            box.querySelector('[data-alias-chip]:last-of-type')?.remove();
        }
    });

    // Lo escrito sin confirmar tambien cuenta: nadie espera perderlo al guardar.
    input.addEventListener('blur', () => {
        addAlias(input.value);
        input.value = '';
    });

    // Pegar "dogo, chupiral, lubrvinal" crea los tres de un jalon.
    input.addEventListener('paste', (e) => {
        const text = (e.clipboardData || window.clipboardData).getData('text');
        if (!text.includes(',')) return;

        e.preventDefault();
        text.split(',').forEach(addAlias);
    });

    box.addEventListener('click', (e) => {
        const remove = e.target.closest('[data-alias-remove]');
        if (remove) {
            remove.closest('[data-alias-chip]').remove();
            return;
        }
        input.focus();
    });
})();
</script>
