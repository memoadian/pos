@extends('layouts.app')
@section('title', 'Cotizador')
@section('content')
<div class="space-y-6">
    <div>
        <h1 class="text-xl font-semibold text-slate-900">Cotizador</h1>
        <p class="text-sm text-slate-500 mt-1">Arma una lista de productos y ajusta el % de ganancia sobre su costo de compra para ver el precio de venta sugerido.</p>
    </div>

    {{-- Buscador de productos y alta manual --}}
    <div class="bg-white rounded-lg border border-slate-200 p-4 space-y-3 print:hidden">
        <div class="flex flex-col md:flex-row gap-3">
            <div class="flex-1 relative">
                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400"><i class="bi bi-search"></i></span>
                <input type="text" id="searchInput" placeholder="Buscar producto por nombre o código..." autocomplete="off"
                    class="w-full pl-10 pr-4 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none transition">
                <div id="searchResults" class="hidden absolute z-20 mt-1 w-full bg-white border border-slate-200 rounded-lg shadow-lg max-h-72 overflow-auto"></div>
            </div>
            <div class="flex items-center gap-2">
                <label for="defaultMargin" class="text-sm text-slate-600 whitespace-nowrap">% ganancia por defecto</label>
                <input type="number" id="defaultMargin" value="30" min="0" max="100" step="0.5"
                    class="w-20 px-2 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none">
                <button type="button" id="applyMarginToAllBtn"
                    class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-medium rounded-lg transition-colors whitespace-nowrap">
                    Aplicar a todas
                </button>
            </div>
        </div>

        {{-- Alta manual (productos que no están en el catálogo) --}}
        <div class="flex flex-col md:flex-row gap-2 items-stretch md:items-end border-t border-slate-100 pt-3">
            <div class="flex-1">
                <label for="manualName" class="block text-xs text-slate-500 mb-1">Producto manual (nombre)</label>
                <input type="text" id="manualName" placeholder="Ej. Cloro"
                    class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none">
            </div>
            <div class="w-full md:w-32">
                <label for="manualCost" class="block text-xs text-slate-500 mb-1">Costo</label>
                <input type="number" id="manualCost" min="0" step="0.01" placeholder="0.00"
                    class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none">
            </div>
            <button type="button" id="addManualBtn"
                class="px-4 py-2 bg-cyan-600 hover:bg-cyan-700 text-white text-sm font-medium rounded-lg transition-colors whitespace-nowrap">
                <i class="bi bi-plus-lg"></i> Agregar
            </button>
        </div>
    </div>

    {{-- Lista de la cotización --}}
    <div class="bg-white rounded-lg border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-50 border-b border-slate-200 print:hidden">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-600 uppercase tracking-wider">Producto</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-600 uppercase tracking-wider">Costo</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-600 uppercase tracking-wider">% Ganancia</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-600 uppercase tracking-wider">Precio venta</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-600 uppercase tracking-wider">Cant.</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-600 uppercase tracking-wider">Subtotal</th>
                        <th class="px-4 py-3 print:hidden"></th>
                    </tr>
                </thead>
                <tbody id="quoteBody" class="divide-y divide-slate-200"></tbody>
            </table>
        </div>
        <div id="emptyState" class="text-center text-slate-500 py-10">
            <i class="bi bi-calculator text-4xl text-slate-300 mb-3 block"></i>
            <p>Busca un producto o agrega uno manual para empezar</p>
        </div>
    </div>

    {{-- Totales --}}
    <div id="totalsCard" class="hidden bg-white rounded-lg border border-slate-200 p-4">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
            <div class="grid grid-cols-3 gap-6 text-sm">
                <div>
                    <p class="text-slate-500">Costo total</p>
                    <p class="font-semibold text-slate-900" id="totalCost">{{ money(0) }}</p>
                </div>
                <div>
                    <p class="text-slate-500">Ganancia total</p>
                    <p class="font-semibold text-emerald-600" id="totalProfit">{{ money(0) }} (0%)</p>
                </div>
                <div>
                    <p class="text-slate-500">Total a cobrar</p>
                    <p class="font-semibold text-slate-900 text-lg" id="totalSale">{{ money(0) }}</p>
                </div>
            </div>
            <div class="flex items-center gap-2 print:hidden">
                <button type="button" id="printBtn" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-medium rounded-lg transition-colors">
                    <i class="bi bi-printer"></i> Imprimir
                </button>
                <button type="button" id="clearBtn" class="px-4 py-2 bg-red-50 hover:bg-red-100 text-red-600 text-sm font-medium rounded-lg transition-colors">
                    <i class="bi bi-trash"></i> Vaciar
                </button>
            </div>
        </div>
    </div>
</div>

<style>
    @media print {
        body * { visibility: hidden; }
        #printArea, #printArea * { visibility: visible; }
        #printArea { position: absolute; top: 0; left: 0; width: 100%; padding: 1rem; }
    }
</style>
<div id="printArea" class="hidden"></div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const currencySymbol = @json(setting('currency_symbol', '$'));
    const searchInput = document.getElementById('searchInput');
    const searchResults = document.getElementById('searchResults');
    const defaultMarginInput = document.getElementById('defaultMargin');
    const applyMarginToAllBtn = document.getElementById('applyMarginToAllBtn');
    const manualNameInput = document.getElementById('manualName');
    const manualCostInput = document.getElementById('manualCost');
    const addManualBtn = document.getElementById('addManualBtn');
    const quoteBody = document.getElementById('quoteBody');
    const emptyState = document.getElementById('emptyState');
    const totalsCard = document.getElementById('totalsCard');
    const totalCostEl = document.getElementById('totalCost');
    const totalProfitEl = document.getElementById('totalProfit');
    const totalSaleEl = document.getElementById('totalSale');
    const printBtn = document.getElementById('printBtn');
    const clearBtn = document.getElementById('clearBtn');
    const printArea = document.getElementById('printArea');

    let lines = [];
    let nextId = 1;
    let debounceTimer;

    function money(amount) {
        return currencySymbol + (Number(amount) || 0).toFixed(2);
    }

    function escapeHtml(str) {
        return String(str).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    }

    function clampMargin(value) {
        value = Number(value);
        if (Number.isNaN(value)) return 0;
        return Math.min(100, Math.max(0, value));
    }

    function priceFor(line) {
        return line.cost * (1 + line.marginPct / 100);
    }

    function addLine(line) {
        lines.push(line);
        renderTable();
    }

    function removeLine(id) {
        lines = lines.filter((l) => l.id !== id);
        renderTable();
    }

    function renderTable() {
        emptyState.classList.toggle('hidden', lines.length > 0);
        totalsCard.classList.toggle('hidden', lines.length === 0);

        quoteBody.innerHTML = lines.map((line) => {
            const price = priceFor(line);
            const subtotal = price * line.qty;
            return `
                <tr data-row="${line.id}">
                    <td class="px-4 py-3">
                        <div class="font-medium text-slate-900">${escapeHtml(line.name)}</div>
                        ${line.barcode ? `<div class="text-xs text-slate-400">${escapeHtml(line.barcode)}</div>` : ''}
                    </td>
                    <td class="px-4 py-3">
                        <input type="number" min="0" step="0.01" value="${line.cost}" data-field="cost"
                            class="w-24 px-2 py-1 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none">
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-2">
                            <input type="range" min="0" max="100" step="1" value="${line.marginPct}" data-field="marginRange" class="w-24 accent-cyan-600">
                            <div class="relative">
                                <input type="number" min="0" max="100" step="0.5" value="${line.marginPct}" data-field="marginNumber"
                                    class="w-16 pr-5 px-2 py-1 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-cyan-500 outline-none">
                                <span class="absolute right-2 top-1/2 -translate-y-1/2 text-xs text-slate-400 pointer-events-none">%</span>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3 font-medium text-emerald-700" data-cell="price">${money(price)}</td>
                    <td class="px-4 py-3">
                        <input type="number" min="1" step="1" value="${line.qty}" data-field="qty"
                            class="w-16 px-2 py-1 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-cyan-500 outline-none">
                    </td>
                    <td class="px-4 py-3 font-medium text-slate-900" data-cell="subtotal">${money(subtotal)}</td>
                    <td class="px-4 py-3 text-right print:hidden">
                        <button type="button" data-action="remove" class="text-red-500 hover:text-red-700" title="Quitar">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
        }).join('');

        updateTotals();
    }

    function updateRowCalc(id) {
        const line = lines.find((l) => l.id === id);
        if (!line) return;
        const row = quoteBody.querySelector(`tr[data-row="${id}"]`);
        if (!row) return;
        const price = priceFor(line);
        const subtotal = price * line.qty;
        row.querySelector('[data-cell="price"]').textContent = money(price);
        row.querySelector('[data-cell="subtotal"]').textContent = money(subtotal);
        updateTotals();
    }

    function updateTotals() {
        let totalCost = 0;
        let totalSale = 0;
        lines.forEach((line) => {
            totalCost += line.cost * line.qty;
            totalSale += priceFor(line) * line.qty;
        });
        const profit = totalSale - totalCost;
        const profitPct = totalCost > 0 ? (profit / totalCost) * 100 : 0;
        totalCostEl.textContent = money(totalCost);
        totalSaleEl.textContent = money(totalSale);
        totalProfitEl.textContent = `${money(profit)} (${profitPct.toFixed(1)}%)`;
    }

    // Edición inline de costo, % ganancia y cantidad (delegado en el tbody
    // para no tener que re-renderizar toda la tabla y perder el foco/drag
    // del slider en cada tecleo).
    quoteBody.addEventListener('input', function (e) {
        const row = e.target.closest('tr[data-row]');
        if (!row) return;
        const id = Number(row.dataset.row);
        const line = lines.find((l) => l.id === id);
        if (!line) return;
        const field = e.target.dataset.field;

        if (field === 'cost') {
            line.cost = Math.max(0, Number(e.target.value) || 0);
        } else if (field === 'qty') {
            line.qty = Math.max(1, parseInt(e.target.value, 10) || 1);
        } else if (field === 'marginRange' || field === 'marginNumber') {
            line.marginPct = clampMargin(e.target.value);
            const range = row.querySelector('[data-field="marginRange"]');
            const number = row.querySelector('[data-field="marginNumber"]');
            if (field === 'marginRange') number.value = line.marginPct;
            else range.value = line.marginPct;
        } else {
            return;
        }

        updateRowCalc(id);
    });

    quoteBody.addEventListener('click', function (e) {
        const btn = e.target.closest('[data-action="remove"]');
        if (!btn) return;
        const row = btn.closest('tr[data-row]');
        removeLine(Number(row.dataset.row));
    });

    applyMarginToAllBtn.addEventListener('click', function () {
        const value = clampMargin(defaultMarginInput.value);
        lines.forEach((line) => { line.marginPct = value; });
        renderTable();
    });

    addManualBtn.addEventListener('click', function () {
        const name = manualNameInput.value.trim();
        const cost = Math.max(0, Number(manualCostInput.value) || 0);
        if (!name) {
            manualNameInput.focus();
            return;
        }
        addLine({
            id: nextId++,
            name,
            barcode: null,
            cost,
            marginPct: clampMargin(defaultMarginInput.value),
            qty: 1,
        });
        manualNameInput.value = '';
        manualCostInput.value = '';
        manualNameInput.focus();
    });

    clearBtn.addEventListener('click', function () {
        if (lines.length && !confirm('¿Vaciar la lista de la cotización?')) return;
        lines = [];
        renderTable();
    });

    printBtn.addEventListener('click', function () {
        const rows = lines.map((line) => {
            const price = priceFor(line);
            const subtotal = price * line.qty;
            return `
                <tr>
                    <td style="padding:6px 8px;border-bottom:1px solid #e2e8f0;">${escapeHtml(line.name)}</td>
                    <td style="padding:6px 8px;border-bottom:1px solid #e2e8f0;">${money(line.cost)}</td>
                    <td style="padding:6px 8px;border-bottom:1px solid #e2e8f0;">${line.marginPct}%</td>
                    <td style="padding:6px 8px;border-bottom:1px solid #e2e8f0;">${money(price)}</td>
                    <td style="padding:6px 8px;border-bottom:1px solid #e2e8f0;">${line.qty}</td>
                    <td style="padding:6px 8px;border-bottom:1px solid #e2e8f0;">${money(subtotal)}</td>
                </tr>
            `;
        }).join('');

        printArea.innerHTML = `
            <h1 style="font-size:18px;margin-bottom:12px;">Cotización</h1>
            <table style="width:100%;border-collapse:collapse;font-size:13px;">
                <thead>
                    <tr style="text-align:left;background:#f8fafc;">
                        <th style="padding:6px 8px;">Producto</th>
                        <th style="padding:6px 8px;">Costo</th>
                        <th style="padding:6px 8px;">% Ganancia</th>
                        <th style="padding:6px 8px;">Precio venta</th>
                        <th style="padding:6px 8px;">Cant.</th>
                        <th style="padding:6px 8px;">Subtotal</th>
                    </tr>
                </thead>
                <tbody>${rows}</tbody>
            </table>
            <p style="margin-top:12px;font-size:14px;font-weight:600;text-align:right;">
                Total: ${totalSaleEl.textContent}
            </p>
        `;
        window.print();
    });

    // Búsqueda de productos del catálogo (nombre o código de barras).
    searchInput.addEventListener('input', function () {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(runSearch, 300);
    });

    document.addEventListener('click', function (e) {
        if (!searchResults.contains(e.target) && e.target !== searchInput) {
            searchResults.classList.add('hidden');
        }
    });

    function runSearch() {
        const query = searchInput.value.trim();
        if (query.length < 1) {
            searchResults.classList.add('hidden');
            searchResults.innerHTML = '';
            return;
        }

        const url = new URL('{{ route("cotizador.products.search") }}');
        url.searchParams.append('query', query);

        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
            .then((r) => r.json())
            .then((data) => renderSearchResults(data.products))
            .catch((e) => console.error('Error:', e));
    }

    function renderSearchResults(products) {
        if (products.length === 0) {
            searchResults.innerHTML = `<div class="p-3 text-sm text-slate-500">No se encontraron productos</div>`;
            searchResults.classList.remove('hidden');
            return;
        }

        searchResults.innerHTML = products.map((p) => `
            <button type="button" data-product='${JSON.stringify(p)}' class="w-full text-left px-4 py-2 hover:bg-slate-50 border-b border-slate-100 last:border-0 flex items-center justify-between gap-2">
                <span>
                    <span class="block text-sm font-medium text-slate-900">${escapeHtml(p.name)}</span>
                    ${p.barcode ? `<span class="block text-xs text-slate-400">${escapeHtml(p.barcode)}</span>` : ''}
                </span>
                <span class="text-sm text-slate-600 whitespace-nowrap">Costo: ${money(p.cost)}</span>
            </button>
        `).join('');
        searchResults.classList.remove('hidden');

        searchResults.querySelectorAll('[data-product]').forEach((btn) => {
            btn.addEventListener('click', function () {
                const p = JSON.parse(this.dataset.product);
                addLine({
                    id: nextId++,
                    name: p.name,
                    barcode: p.barcode,
                    cost: Number(p.cost) || 0,
                    marginPct: clampMargin(defaultMarginInput.value),
                    qty: 1,
                });
                searchInput.value = '';
                searchResults.classList.add('hidden');
                searchResults.innerHTML = '';
            });
        });
    }
});
</script>
@endsection
