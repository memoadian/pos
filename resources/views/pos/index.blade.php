@extends('layouts.app')
@section('title', 'Punto de Venta')
@section('content')
<div class="h-[calc(100vh-120px)] flex flex-col print:hidden">
    {{-- Header del POS --}}
    <div class="bg-white border-b border-slate-200 px-4 py-3 flex justify-between items-center flex-shrink-0">
        <div class="flex items-center gap-4">
            <div class="flex items-center gap-2">
                <i class="bi bi-shop text-cyan-600"></i>
                <span class="font-medium text-slate-900">{{ $branch->name }}</span>
            </div>
            <span class="text-slate-400">|</span>
            <div class="flex items-center gap-2 text-sm text-slate-600">
                <i class="bi bi-cash-stack"></i>
                <span>Caja #{{ $cashRegister->id }}</span>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('cash-register.close') }}" class="text-sm text-red-600 hover:text-red-700 flex items-center gap-1">
                <i class="bi bi-x-circle"></i>
                <span>Cerrar Caja</span>
            </a>
        </div>
    </div>

    {{-- Contenido Principal: 2 columnas (apiladas en pantallas angostas) --}}
    <div class="flex-1 flex flex-col lg:flex-row overflow-hidden">
        {{-- Columna Izquierda: Buscador --}}
        <div class="w-full lg:w-1/2 flex-1 lg:border-r border-b lg:border-b-0 border-slate-200 flex flex-col bg-white min-h-0">
            {{-- Buscador --}}
            <div class="p-4 border-b border-slate-200">
                <div class="relative">
                    <label for="searchInput" class="sr-only">Buscar producto por nombre o código</label>
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400">
                        <i class="bi bi-search"></i>
                    </span>
                    <input type="text"
                           id="searchInput"
                           placeholder="Buscar producto (nombre o codigo)..."
                           class="w-full pl-10 pr-4 py-3 border border-slate-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none transition"
                           autocomplete="off"
                           autofocus>
                </div>
                <label class="flex items-center gap-2 mt-2 text-sm text-slate-600 cursor-pointer" title="Muestra si hay stock en otras sucursales. Solo puedes vender el stock de tu sucursal actual.">
                    <input type="checkbox" id="allBranches" class="rounded border-slate-300 text-cyan-600 focus:ring-cyan-500">
                    <span>Buscar en todas las sucursales</span>
                </label>
            </div>

            {{-- Resultados --}}
            <div class="flex-1 overflow-auto p-4" id="searchResults" role="region" aria-live="polite" aria-label="Resultados de busqueda">
                <div class="text-center text-slate-500 py-8">
                    <i class="bi bi-search text-4xl text-slate-300 mb-3 block"></i>
                    <p>Escribe para buscar productos...</p>
                    <p class="text-xs mt-1">o escanea un codigo de barras</p>
                </div>
            </div>
        </div>

        {{-- Columna Derecha: Carrito --}}
        <div class="w-full lg:w-1/2 flex-1 flex flex-col bg-slate-50 min-h-0">
            {{-- Header Carrito --}}
            <div class="px-4 py-3 bg-white border-b border-slate-200 flex items-center justify-between">
                <h2 class="font-semibold text-slate-900">
                    <i class="bi bi-cart3 mr-2"></i>Orden Actual
                </h2>
                <button onclick="cart.clear()" id="clearCartBtn" class="text-sm text-red-600 hover:text-red-700 hidden">
                    <i class="bi bi-trash mr-1"></i>Vaciar
                </button>
            </div>

            {{-- Items del carrito --}}
            <div class="flex-1 overflow-auto p-4" id="cartItems">
                <div class="text-center text-slate-500 py-8" id="emptyCartMessage">
                    <i class="bi bi-cart text-4xl text-slate-300 mb-3 block"></i>
                    <p>Agrega productos para comenzar</p>
                </div>
            </div>

            {{-- Totales y Cobrar --}}
            <div class="bg-white border-t border-slate-200 p-4 flex-shrink-0">
                <div class="space-y-2 mb-4">
                    <div class="flex justify-between text-sm text-slate-500">
                        <span>Articulos:</span>
                        <span id="itemCount">0</span>
                    </div>
                    <div class="flex justify-between text-slate-600">
                        <span>Subtotal:</span>
                        <span id="subtotal">$0.00</span>
                    </div>
                    <div class="flex justify-between text-xl font-bold text-slate-900">
                        <span>Total:</span>
                        <span id="total">$0.00</span>
                    </div>
                </div>

                <select id="paymentMethod" class="w-full mb-3 px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none">
                    <option value="efectivo">Efectivo</option>
                    <option value="tarjeta">Tarjeta</option>
                    <option value="transferencia">Transferencia</option>
                </select>

                <div id="cashAmountSection" class="mb-3 space-y-2">
                    <div>
                        <label for="amountReceived" class="block text-xs font-medium text-slate-500 mb-1">Monto recibido</label>
                        <input type="number" id="amountReceived" step="0.01" min="0"
                               class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none">
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-slate-600">Cambio:</span>
                        <span id="changeAmount" class="font-semibold text-slate-900">$0.00</span>
                    </div>
                </div>

                <button id="checkoutBtn"
                        onclick="processCheckout()"
                        class="w-full py-3 bg-emerald-600 text-white font-medium rounded-lg hover:bg-emerald-700 disabled:bg-slate-300 disabled:cursor-not-allowed transition-colors flex items-center justify-center gap-2"
                        disabled>
                    <i class="bi bi-cash-coin"></i>
                    <span>Cobrar (F9)</span>
                </button>
            </div>
        </div>
    </div>

    {{-- Footer: Atajos --}}
    <div class="bg-slate-800 text-white px-4 py-2 text-sm flex gap-6 flex-shrink-0">
        <span><kbd class="bg-slate-700 px-2 py-0.5 rounded text-xs">F2</kbd> Buscar</span>
        <span><kbd class="bg-slate-700 px-2 py-0.5 rounded text-xs">Enter</kbd> Agregar</span>
        <span><kbd class="bg-slate-700 px-2 py-0.5 rounded text-xs">F9</kbd> Cobrar</span>
        <span><kbd class="bg-slate-700 px-2 py-0.5 rounded text-xs">Esc</kbd> Cancelar</span>
    </div>
</div>

{{-- Toast Container --}}
<div id="toastContainer" class="fixed top-4 right-4 z-50 space-y-2 print:hidden" role="status" aria-live="polite"></div>

{{-- Modal de Ticket / Resumen de Venta --}}
<div id="ticketModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4 print:bg-white">
    <div class="bg-white rounded-lg w-full max-w-sm max-h-[90vh] overflow-y-auto print:max-w-none print:max-h-none print:rounded-none print:overflow-visible">
        <div class="flex justify-between items-center px-4 py-3 border-b border-slate-200 print:hidden">
            <h2 class="font-semibold text-slate-900">
                <i class="bi bi-check-circle text-emerald-600 mr-1"></i>
                Venta completada
            </h2>
            <button type="button" id="closeTicketModalBtn" class="text-slate-500 hover:text-slate-700 text-2xl leading-none">&times;</button>
        </div>

        <div id="ticketContent" class="p-4 font-mono text-xs leading-relaxed w-[80mm] mx-auto print:w-[80mm]"></div>

        <div class="flex gap-2 px-4 py-3 border-t border-slate-200 print:hidden">
            <button type="button" id="printTicketBtn"
                    class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2 bg-slate-800 hover:bg-slate-900 text-white text-sm font-medium rounded-lg transition-colors">
                <i class="bi bi-printer"></i>
                <span>Imprimir Ticket</span>
            </button>
            <button type="button" id="closeTicketBtn"
                    class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-medium rounded-lg transition-colors">
                Cerrar
            </button>
        </div>
    </div>
</div>

<style>
    @media print {
        @page { size: 80mm auto; margin: 0; }
        body * { visibility: hidden; }
        #ticketModal, #ticketModal * { visibility: visible; }
        #ticketModal { position: absolute; inset: 0; }
    }
</style>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Estado del carrito (persistido en localStorage por caja, para no perder
    // la venta en curso si se recarga la pagina o se navega por accidente)
    const CART_STORAGE_KEY = 'pos_cart_{{ $cashRegister->id }}';

    const cart = {
        items: [],

        save() {
            try {
                localStorage.setItem(CART_STORAGE_KEY, JSON.stringify(this.items));
            } catch (e) {
                // localStorage no disponible (modo privado, cuota llena, etc.) - no es fatal
            }
        },

        load() {
            try {
                const saved = localStorage.getItem(CART_STORAGE_KEY);
                if (saved) this.items = JSON.parse(saved);
            } catch (e) {
                this.items = [];
            }
        },

        add(product) {
            const existing = this.items.find(i => i.product_id === product.id);
            if (existing) {
                const newQty = existing.quantity + 1;
                if (newQty <= product.stock) {
                    existing.quantity = newQty;
                    existing.unit_price = this.getPriceForQuantity(product, newQty);
                } else {
                    showToast('Stock insuficiente', 'error');
                    return;
                }
            } else {
                if (product.stock <= 0) {
                    showToast('Producto sin stock', 'error');
                    return;
                }
                this.items.push({
                    product_id: product.id,
                    name: product.name,
                    quantity: 1,
                    unit_price: product.price_retail,
                    cost: product.cost,
                    stock: product.stock,
                    allows_decimals: product.allows_decimals,
                    price_retail: product.price_retail,
                    price_wholesale: product.price_wholesale,
                    price_super_wholesale: product.price_super_wholesale,
                    min_wholesale_qty: product.min_wholesale_qty,
                    min_super_wholesale_qty: product.min_super_wholesale_qty,
                });
            }
            this.render();
        },

        getPriceForQuantity(product, qty) {
            if (product.min_super_wholesale_qty && qty >= product.min_super_wholesale_qty) {
                return product.price_super_wholesale;
            }
            if (product.min_wholesale_qty && qty >= product.min_wholesale_qty) {
                return product.price_wholesale;
            }
            return product.price_retail;
        },

        getPriceLevelName(product, qty) {
            if (product.min_super_wholesale_qty && qty >= product.min_super_wholesale_qty) {
                return 'Super Mayoreo';
            }
            if (product.min_wholesale_qty && qty >= product.min_wholesale_qty) {
                return 'Mayoreo';
            }
            return 'Menudeo';
        },

        updateQuantity(productId, quantity) {
            const item = this.items.find(i => i.product_id === productId);
            if (!item) return;

            const step = item.allows_decimals ? 0.01 : 1;
            quantity = Math.round(quantity / step) * step;
            quantity = Math.max(0, quantity);

            if (quantity <= 0) {
                this.remove(productId);
                return;
            }

            if (quantity > item.stock) {
                showToast('Stock insuficiente', 'error');
                this.render(); // revierte el input al valor real (evita que se quede mostrando la cantidad rechazada)
                return;
            }

            item.quantity = quantity;
            item.unit_price = this.getPriceForQuantity(item, quantity);
            this.render();
        },

        remove(productId) {
            this.items = this.items.filter(i => i.product_id !== productId);
            this.render();
        },

        clear() {
            if (this.items.length > 0 && !confirm('¿Vaciar el carrito?')) return;
            this.items = [];
            this.render();
            if (typeof resetCashAmount === 'function') resetCashAmount();
        },

        getTotal() {
            return this.items.reduce((sum, item) => sum + (item.quantity * item.unit_price), 0);
        },

        render() {
            const container = document.getElementById('cartItems');
            const checkoutBtn = document.getElementById('checkoutBtn');
            const clearBtn = document.getElementById('clearCartBtn');
            const emptyMessage = document.getElementById('emptyCartMessage');

            if (this.items.length === 0) {
                container.innerHTML = `
                    <div class="text-center text-slate-500 py-8" id="emptyCartMessage">
                        <i class="bi bi-cart text-4xl text-slate-300 mb-3 block"></i>
                        <p>Agrega productos para comenzar</p>
                    </div>
                `;
                checkoutBtn.disabled = true;
                clearBtn.classList.add('hidden');
                document.getElementById('itemCount').textContent = '0';
                document.getElementById('subtotal').textContent = '$0.00';
                document.getElementById('total').textContent = '$0.00';
                this.save();
                return;
            }

            clearBtn.classList.remove('hidden');

            container.innerHTML = this.items.map(item => {
                const priceLevel = this.getPriceLevelName(item, item.quantity);
                const isWholesale = priceLevel !== 'Menudeo';

                return `
                <div class="bg-white rounded-lg p-3 mb-2 shadow-sm border border-slate-200" data-product-id="${item.product_id}">
                    <div class="flex justify-between items-start mb-2">
                        <div class="flex-1">
                            <span class="font-medium text-slate-900">${item.name}</span>
                            ${isWholesale ? `<span class="ml-2 text-xs px-1.5 py-0.5 rounded bg-purple-100 text-purple-700">${priceLevel}</span>` : ''}
                        </div>
                        <button onclick="cart.remove(${item.product_id})"
                                aria-label="Quitar ${escapeHtml(item.name)} del carrito"
                                class="text-red-500 hover:text-red-700 p-1">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <button onclick="cart.updateQuantity(${item.product_id}, ${item.quantity - (item.allows_decimals ? 0.5 : 1)})"
                                    aria-label="Disminuir cantidad de ${escapeHtml(item.name)}"
                                    class="w-8 h-8 bg-slate-200 rounded hover:bg-slate-300 flex items-center justify-center">
                                <i class="bi bi-dash"></i>
                            </button>
                            <input type="number"
                                   value="${item.quantity}"
                                   step="${item.allows_decimals ? '0.01' : '1'}"
                                   min="0.01"
                                   max="${item.stock}"
                                   aria-label="Cantidad de ${escapeHtml(item.name)}"
                                   class="w-20 text-center border border-slate-300 rounded py-1 focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none"
                                   onchange="cart.updateQuantity(${item.product_id}, parseFloat(this.value) || 0)">
                            <button onclick="cart.updateQuantity(${item.product_id}, ${item.quantity + (item.allows_decimals ? 0.5 : 1)})"
                                    aria-label="Aumentar cantidad de ${escapeHtml(item.name)}"
                                    class="w-8 h-8 bg-slate-200 rounded hover:bg-slate-300 flex items-center justify-center">
                                <i class="bi bi-plus"></i>
                            </button>
                        </div>
                        <div class="text-right">
                            <div class="text-sm text-slate-500">$${item.unit_price.toFixed(2)} x ${item.quantity}</div>
                            <div class="font-semibold text-slate-900">$${(item.quantity * item.unit_price).toFixed(2)}</div>
                        </div>
                    </div>
                    ${item.quantity >= item.stock ? '<p class="text-xs text-amber-600 mt-2"><i class="bi bi-exclamation-triangle mr-1"></i>Stock maximo alcanzado</p>' : ''}
                </div>
            `}).join('');

            const total = this.getTotal();
            const totalItems = this.items.reduce((sum, item) => sum + item.quantity, 0);
            document.getElementById('itemCount').textContent = totalItems % 1 === 0 ? totalItems : totalItems.toFixed(2);
            document.getElementById('subtotal').textContent = '$' + total.toFixed(2);
            document.getElementById('total').textContent = '$' + total.toFixed(2);
            checkoutBtn.disabled = false;
            this.save();
            if (typeof updateChangeDisplay === 'function') updateChangeDisplay();
        },

        toPayload() {
            return this.items.map(item => ({
                product_id: item.product_id,
                quantity: item.quantity,
                unit_price: item.unit_price,
            }));
        }
    };

    // Hacer cart global
    window.cart = cart;

    // Restaurar carrito guardado (si la pagina se recargo con una venta en curso)
    cart.load();
    if (cart.items.length > 0) {
        showToast('Se restauro tu venta en curso', 'info');
    }
    cart.render();

    // Pago en efectivo: monto recibido y cambio.
    // Mientras el cajero no toque el campo a mano, se mantiene igual al
    // total (pago exacto = cero pasos extra); en cuanto lo edita, deja de
    // autocompletarse hasta la siguiente venta.
    const paymentMethodSelect = document.getElementById('paymentMethod');
    const cashAmountSection = document.getElementById('cashAmountSection');
    const amountReceivedInput = document.getElementById('amountReceived');
    const changeAmountEl = document.getElementById('changeAmount');
    let amountManuallyEdited = false;

    function updateChangeDisplay() {
        if (paymentMethodSelect.value !== 'efectivo') return;
        if (!amountManuallyEdited) {
            amountReceivedInput.value = cart.getTotal().toFixed(2);
        }
        const received = parseFloat(amountReceivedInput.value) || 0;
        const change = received - cart.getTotal();
        changeAmountEl.textContent = '$' + Math.abs(change).toFixed(2);
        changeAmountEl.classList.toggle('text-red-600', change < 0);
        changeAmountEl.classList.toggle('text-slate-900', change >= 0);
    }

    function resetCashAmount() {
        amountManuallyEdited = false;
        updateChangeDisplay();
    }

    function toggleCashSection() {
        const isCash = paymentMethodSelect.value === 'efectivo';
        cashAmountSection.classList.toggle('hidden', !isCash);
        if (isCash) {
            resetCashAmount();
        }
    }

    paymentMethodSelect.addEventListener('change', toggleCashSection);
    amountReceivedInput.addEventListener('input', function() {
        amountManuallyEdited = true;
        updateChangeDisplay();
    });
    toggleCashSection();

    // Busqueda con debounce
    const searchInput = document.getElementById('searchInput');
    const allBranchesCheckbox = document.getElementById('allBranches');
    const searchResults = document.getElementById('searchResults');
    let debounceTimer;
    let lastResults = [];
    let lastQuery = null; // texto exacto al que corresponde lastResults (null = aun no hay busqueda estable)
    let searchToken = 0;
    let highlightIndex = -1;

    function showSearchPlaceholder() {
        searchResults.innerHTML = `
            <div class="text-center text-slate-500 py-8">
                <i class="bi bi-search text-4xl text-slate-300 mb-3 block"></i>
                <p>Escribe para buscar productos...</p>
            </div>
        `;
    }

    function resetSearchState() {
        searchInput.value = '';
        lastResults = [];
        lastQuery = null;
        highlightIndex = -1;
        showSearchPlaceholder();
    }

    searchInput.addEventListener('input', function() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => searchProducts(), 300);
    });

    allBranchesCheckbox.addEventListener('change', () => {
        if (searchInput.value.trim()) searchProducts();
    });

    // searchProducts siempre dispara una busqueda fresca para el texto actual.
    // Cada llamada tiene un "token"; si llega una respuesta de una busqueda
    // vieja (por ejemplo porque el texto ya cambio) se ignora, para que
    // "lastResults" nunca quede desincronizado de lo que hay en el input
    // (esto es lo que causaba que el lector de codigo de barras agregara
    // a veces el producto de la busqueda anterior).
    function searchProducts(onComplete) {
        const query = searchInput.value.trim();
        const token = ++searchToken;

        if (query.length < 1) {
            showSearchPlaceholder();
            lastResults = [];
            lastQuery = null;
            highlightIndex = -1;
            if (onComplete) onComplete([]);
            return;
        }

        const url = new URL('{{ route("pos.products.search") }}');
        url.searchParams.append('query', query);
        url.searchParams.append('all_branches', allBranchesCheckbox.checked ? '1' : '0');

        searchResults.innerHTML = `
            <div class="text-center text-slate-500 py-8">
                <i class="bi bi-hourglass-split text-4xl text-slate-300 mb-3 block animate-pulse"></i>
                <p>Buscando...</p>
            </div>
        `;

        fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            }
        })
        .then(r => r.json())
        .then(data => {
            if (token !== searchToken) return; // llego una respuesta vieja, se descarta
            lastResults = data.products;
            lastQuery = query;
            renderSearchResults(data.products);
            if (onComplete) onComplete(data.products);
        })
        .catch(e => {
            if (token !== searchToken) return;
            console.error('Error:', e);
            showToast('Error al buscar productos', 'error');
            searchResults.innerHTML = `
                <div class="text-center text-red-500 py-8">
                    <i class="bi bi-exclamation-circle text-4xl mb-3 block"></i>
                    <p>Error al buscar</p>
                </div>
            `;
        });
    }

    function renderSearchResults(products) {
        highlightIndex = products.length > 0 ? 0 : -1;

        if (products.length === 0) {
            searchResults.innerHTML = `
                <div class="text-center text-slate-500 py-8">
                    <i class="bi bi-search text-4xl text-slate-300 mb-3 block"></i>
                    <p>No se encontraron productos</p>
                </div>
            `;
            return;
        }

        searchResults.innerHTML = products.map((product, idx) => {
            const stockClass = {
                'out_of_stock': 'bg-red-100 text-red-700',
                'low_stock': 'bg-amber-100 text-amber-700',
                'in_stock': 'bg-emerald-100 text-emerald-700',
            }[product.stock_status];

            const isDisabled = product.stock <= 0;

            // "Buscar en todas las sucursales" no cambia que se pueda agregar
            // al carrito (solo se puede vender lo que hay en la sucursal
            // actual), pero si debe reflejarse en pantalla cuanto stock hay
            // en la red para que el cajero pueda informar al cliente.
            const otherBranchesStock = Math.max(0, (product.total_stock ?? product.stock) - product.stock);
            let stockLabel = product.stock <= 0 ? 'Sin stock aqui' : 'Stock: ' + product.stock;
            let otherBranchesNote = '';
            if (otherBranchesStock > 0) {
                otherBranchesNote = `<p class="text-xs text-slate-500 mt-0.5">${otherBranchesStock} disponible${otherBranchesStock === 1 ? '' : 's'} en otras sucursales</p>`;
            }

            return `
                <div class="search-result-item bg-white rounded-lg p-3 mb-2 shadow-sm border transition-colors ${isDisabled ? 'opacity-50 border-slate-200' : 'cursor-pointer border-slate-200 hover:border-cyan-300'} ${idx === highlightIndex ? 'border-cyan-400 bg-cyan-50' : ''}"
                     data-index="${idx}"
                     onmouseenter="highlightSearchResult(${idx})"
                     ${isDisabled ? '' : `onclick="addProductFromSearch(${product.id})"`}>
                    <div class="flex justify-between items-start">
                        <div class="flex-1">
                            <p class="font-medium text-slate-900">${product.name}</p>
                            <p class="text-sm text-slate-500">${product.department} | ${product.unit}</p>
                            ${product.barcode ? `<p class="text-xs text-slate-400 mt-0.5">${product.barcode}</p>` : ''}
                        </div>
                        <div class="text-right ml-3">
                            <p class="font-bold text-lg text-slate-900">$${parseFloat(product.price_retail).toFixed(2)}</p>
                            <span class="inline-block px-2 py-0.5 rounded-full text-xs ${stockClass}">
                                ${stockLabel}
                            </span>
                            ${otherBranchesNote}
                        </div>
                    </div>
                    ${product.min_wholesale_qty ? `
                        <div class="mt-2 pt-2 border-t border-slate-100 text-xs text-slate-500">
                            <span class="mr-3">Mayoreo (${product.min_wholesale_qty}+): $${parseFloat(product.price_wholesale).toFixed(2)}</span>
                            ${product.min_super_wholesale_qty ? `<span>Super (${product.min_super_wholesale_qty}+): $${parseFloat(product.price_super_wholesale).toFixed(2)}</span>` : ''}
                        </div>
                    ` : ''}
                </div>
            `;
        }).join('');
    }

    window.highlightSearchResult = function(idx) {
        highlightIndex = idx;
        applyHighlight();
    };

    function applyHighlight() {
        searchResults.querySelectorAll('.search-result-item').forEach(function(el) {
            const idx = parseInt(el.dataset.index, 10);
            if (idx === highlightIndex) {
                el.classList.add('border-cyan-400', 'bg-cyan-50');
            } else {
                el.classList.remove('border-cyan-400', 'bg-cyan-50');
            }
        });
        const activeEl = searchResults.querySelector(`[data-index="${highlightIndex}"]`);
        if (activeEl) activeEl.scrollIntoView({ block: 'nearest' });
    }

    // Clic y Enter agregan el producto de la misma forma: el carrito ya
    // confirma visualmente el cambio, asi que no hace falta un toast extra
    // por cada producto (evita ruido al escanear varios seguidos), y ambos
    // gestos limpian la busqueda para dejarla lista para el siguiente producto.
    window.addProductFromSearch = function(productId) {
        const product = lastResults.find(p => p.id === productId);
        if (product) {
            cart.add(product);
            resetSearchState();
        }
    };

    function addFromResults(products, index) {
        if (products.length === 0) return;
        const product = products[index] ?? products[0];
        if (product.stock > 0) {
            cart.add(product);
            resetSearchState();
        } else {
            showToast('Producto sin stock', 'error');
        }
    }

    searchInput.addEventListener('keydown', function(e) {
        // Flechas: mover el resaltado entre los resultados visibles
        if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
            if (lastResults.length === 0) return;
            e.preventDefault();
            highlightIndex = e.key === 'ArrowDown'
                ? Math.min(highlightIndex + 1, lastResults.length - 1)
                : Math.max(highlightIndex - 1, 0);
            applyHighlight();
            return;
        }

        // Escape: primero limpia la busqueda (no cancela la venta completa).
        // stopPropagation evita que el listener global de Esc interprete el
        // mismo evento como "cancelar venta" cuando solo se queria borrar el buscador.
        if (e.key === 'Escape') {
            if (searchInput.value || lastResults.length > 0) {
                e.preventDefault();
                e.stopPropagation();
                resetSearchState();
            }
            return;
        }

        if (e.key !== 'Enter') return;
        e.preventDefault();

        // Si los resultados en pantalla ya corresponden exactamente a lo que
        // hay escrito, se usa el resaltado actual sin volver a consultar.
        const currentQuery = searchInput.value.trim();
        if (currentQuery === lastQuery && lastResults.length > 0) {
            addFromResults(lastResults, highlightIndex >= 0 ? highlightIndex : 0);
            return;
        }

        // Si no (o si el escaneo llego mas rapido que el debounce/la red),
        // se cancela lo pendiente y se dispara una busqueda fresca para el
        // texto actual, actuando solo sobre esa respuesta especifica (ver fix #2).
        clearTimeout(debounceTimer);
        searchProducts(function(products) {
            addFromResults(products, 0);
        });
    });

    // Checkout
    // La clave de idempotencia se genera una vez por venta y se reutiliza
    // en reintentos (por ejemplo si la respuesta se pierde por un corte de
    // red): el servidor detecta la clave repetida y regresa la misma venta
    // en vez de crear una duplicada. Solo se limpia tras un exito real.
    let currentOrderKey = null;

    window.processCheckout = async function() {
        if (cart.items.length === 0) return;

        const paymentMethod = paymentMethodSelect.value;
        const total = cart.getTotal();
        let amountReceived = null;

        if (paymentMethod === 'efectivo') {
            amountReceived = parseFloat(amountReceivedInput.value) || 0;
            if (amountReceived < total) {
                showToast('El monto recibido es menor al total', 'error');
                return;
            }
        }

        if (!currentOrderKey) {
            currentOrderKey = (crypto.randomUUID ? crypto.randomUUID() : `${Date.now()}-${Math.random()}`);
        }

        const checkoutBtn = document.getElementById('checkoutBtn');
        checkoutBtn.disabled = true;
        checkoutBtn.innerHTML = '<i class="bi bi-hourglass-split animate-pulse mr-2"></i>Procesando...';

        // Revalidar stock antes de cobrar: si otro cajero vendio el mismo
        // producto mientras estaba en este carrito, se avisa aqui mismo en
        // vez de esperar a que el cobro completo falle.
        try {
            const validation = await fetch('{{ route("pos.validate-stock") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ items: cart.toPayload() })
            }).then(r => r.json());

            if (!validation.all_valid) {
                const invalidNames = validation.items
                    .filter(i => !i.valid)
                    .map(i => {
                        const cartItem = cart.items.find(c => c.product_id === i.product_id);
                        return `${cartItem ? cartItem.name : 'Producto'} (disponible: ${i.available})`;
                    })
                    .join(', ');
                showToast(`Stock insuficiente: ${invalidNames}`, 'error');
                checkoutBtn.disabled = false;
                checkoutBtn.innerHTML = '<i class="bi bi-cash-coin"></i><span>Cobrar (F9)</span>';
                return;
            }
        } catch (error) {
            // Si la validacion no responde, se sigue con el cobro normal:
            // este mismo chequeo se vuelve a hacer dentro de la transaccion del servidor.
            console.error('Error validando stock:', error);
        }

        try {
            const response = await fetch('{{ route("pos.checkout") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({
                    items: cart.toPayload(),
                    payment_method: paymentMethod,
                    idempotency_key: currentOrderKey,
                })
            });

            const data = await response.json();

            if (data.success) {
                const ticketSale = { ...data.sale };
                if (paymentMethod === 'efectivo' && amountReceived !== null) {
                    ticketSale.amount_received = amountReceived;
                    ticketSale.change = amountReceived - parseFloat(data.sale.total);
                }

                currentOrderKey = null;
                cart.items = [];
                cart.render();
                resetSearchState();
                resetCashAmount();
                openTicketModal(ticketSale);
            } else {
                showToast(data.message || 'Error al procesar la venta', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            showToast('Error de conexion. Si la venta ya se registro, reintentar es seguro.', 'error');
        } finally {
            checkoutBtn.disabled = cart.items.length === 0;
            checkoutBtn.innerHTML = '<i class="bi bi-cash-coin"></i><span>Cobrar (F9)</span>';
        }
    };

    // Ticket / Resumen de venta
    const ticketModal = document.getElementById('ticketModal');
    const ticketContent = document.getElementById('ticketContent');
    const paymentMethodLabels = {
        efectivo: 'Efectivo',
        tarjeta: 'Tarjeta',
        transferencia: 'Transferencia',
    };

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    function buildTicketHtml(sale) {
        const itemsHtml = sale.items.map(item => `
            <div class="flex justify-between gap-2">
                <span>${item.quantity}x ${escapeHtml(item.name)}</span>
                <span class="whitespace-nowrap">$${item.total.toFixed(2)}</span>
            </div>
        `).join('');

        const cashLinesHtml = (sale.payment_method === 'efectivo' && typeof sale.amount_received === 'number') ? `
            <div class="flex justify-between mt-1">
                <span>Recibido:</span>
                <span>$${sale.amount_received.toFixed(2)}</span>
            </div>
            <div class="flex justify-between">
                <span>Cambio:</span>
                <span>$${sale.change.toFixed(2)}</span>
            </div>
        ` : '';

        return `
            <div class="text-center mb-2">
                <p class="font-bold text-sm">{{ config('app.name', 'POS') }}</p>
                <p>${escapeHtml(sale.branch)}</p>
            </div>
            <div class="border-t border-dashed border-slate-400 my-2"></div>
            <div>Ticket: #${sale.id}</div>
            <div>Fecha: ${sale.date}</div>
            <div>Cajero: ${escapeHtml(sale.cashier)}</div>
            <div class="border-t border-dashed border-slate-400 my-2"></div>
            ${itemsHtml}
            <div class="border-t border-dashed border-slate-400 my-2"></div>
            <div class="flex justify-between">
                <span>Subtotal:</span>
                <span>$${sale.subtotal.toFixed(2)}</span>
            </div>
            <div class="flex justify-between font-bold text-sm">
                <span>Total:</span>
                <span>$${sale.total.toFixed(2)}</span>
            </div>
            <div class="flex justify-between mt-1">
                <span>Pago:</span>
                <span>${paymentMethodLabels[sale.payment_method] || sale.payment_method}</span>
            </div>
            ${cashLinesHtml}
            <div class="border-t border-dashed border-slate-400 my-2"></div>
            <p class="text-center mt-2">¡Gracias por su compra!</p>
        `;
    }

    function openTicketModal(sale) {
        ticketContent.innerHTML = buildTicketHtml(sale);
        ticketModal.classList.remove('hidden');
        ticketModal.classList.add('flex');
    }

    function closeTicketModal() {
        ticketModal.classList.add('hidden');
        ticketModal.classList.remove('flex');
        searchInput.focus();
    }

    document.getElementById('closeTicketModalBtn').addEventListener('click', closeTicketModal);
    document.getElementById('closeTicketBtn').addEventListener('click', closeTicketModal);
    document.getElementById('printTicketBtn').addEventListener('click', () => window.print());
    ticketModal.addEventListener('click', (e) => {
        if (e.target === ticketModal) closeTicketModal();
    });

    // Atajos de teclado
    document.addEventListener('keydown', function(e) {
        // F2: Focus en buscador
        if (e.key === 'F2') {
            e.preventDefault();
            searchInput.focus();
            searchInput.select();
        }

        // F9: Cobrar
        if (e.key === 'F9') {
            e.preventDefault();
            if (!document.getElementById('checkoutBtn').disabled) {
                processCheckout();
            }
        }

        // Escape: cerrar ticket si esta abierto, si no, limpiar carrito (con confirmacion)
        if (e.key === 'Escape' && !ticketModal.classList.contains('hidden')) {
            closeTicketModal();
            return;
        }
        if (e.key === 'Escape' && cart.items.length > 0) {
            if (confirm('¿Cancelar venta actual?')) {
                cart.items = [];
                cart.render();
                showToast('Venta cancelada', 'info');
            }
        }
    });

    // Toast notifications
    function showToast(message, type = 'info') {
        const container = document.getElementById('toastContainer');
        const colors = {
            success: 'bg-emerald-500',
            error: 'bg-red-500',
            info: 'bg-cyan-500',
            warning: 'bg-amber-500',
        };
        const icons = {
            success: 'bi-check-circle',
            error: 'bi-exclamation-circle',
            info: 'bi-info-circle',
            warning: 'bi-exclamation-triangle',
        };

        const toast = document.createElement('div');
        toast.className = `${colors[type]} text-white px-4 py-3 rounded-lg shadow-lg transform transition-all duration-300 translate-x-full`;
        toast.innerHTML = `
            <div class="flex items-center gap-2">
                <i class="bi ${icons[type]}"></i>
                <span>${message}</span>
            </div>
        `;

        container.appendChild(toast);

        // Animate in
        setTimeout(() => toast.classList.remove('translate-x-full'), 10);

        // Remove after 3s
        setTimeout(() => {
            toast.classList.add('translate-x-full');
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }

    window.showToast = showToast;
});
</script>
@endsection
