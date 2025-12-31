@extends('layouts.app')
@section('title', 'Punto de Venta')
@section('content')
<div class="h-[calc(100vh-120px)] flex flex-col">
    {{-- Header del POS --}}
    <div class="bg-white border-b border-slate-200 px-4 py-3 flex justify-between items-center flex-shrink-0">
        <div class="flex items-center gap-4">
            <div class="flex items-center gap-2">
                <i class="bi bi-shop text-cyan-600"></i>
                <span class="font-medium text-slate-900">{{ $branch->name }}</span>
            </div>
            <span class="text-slate-400">|</span>
            @if ($cashRegister)
                <div class="flex items-center gap-2 text-sm text-slate-600">
                    <i class="bi bi-cash-stack"></i>
                    <span>Caja #{{ $cashRegister->id }}</span>
                </div>
            @else
                <div class="flex items-center gap-2 text-sm text-amber-600 bg-amber-50 px-2 py-1 rounded">
                    <i class="bi bi-eye"></i>
                    <span>Modo Demo</span>
                </div>
            @endif
        </div>
        <div class="flex items-center gap-3">
            @if ($cashRegister)
                <a href="{{ route('cash-register.close') }}" class="text-sm text-red-600 hover:text-red-700 flex items-center gap-1">
                    <i class="bi bi-x-circle"></i>
                    <span>Cerrar Caja</span>
                </a>
            @else
                <div class="text-sm text-slate-500 text-right">
                    <p class="text-xs">Sin caja abierta</p>
                    <p class="text-xs text-amber-600">Solo visualización</p>
                </div>
            @endif
        </div>
    </div>

    {{-- Contenido Principal: 2 columnas --}}
    <div class="flex-1 flex overflow-hidden">
        {{-- Columna Izquierda: Buscador --}}
        <div class="w-1/2 border-r border-slate-200 flex flex-col bg-white">
            {{-- Buscador --}}
            <div class="p-4 border-b border-slate-200">
                <div class="relative">
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
                <label class="flex items-center gap-2 mt-2 text-sm text-slate-600 cursor-pointer">
                    <input type="checkbox" id="allBranches" class="rounded border-slate-300 text-cyan-600 focus:ring-cyan-500">
                    <span>Buscar en todas las sucursales</span>
                </label>
            </div>

            {{-- Resultados --}}
            <div class="flex-1 overflow-auto p-4" id="searchResults">
                <div class="text-center text-slate-500 py-8">
                    <i class="bi bi-search text-4xl text-slate-300 mb-3 block"></i>
                    <p>Escribe para buscar productos...</p>
                    <p class="text-xs mt-1">o escanea un codigo de barras</p>
                </div>
            </div>
        </div>

        {{-- Columna Derecha: Carrito --}}
        <div class="w-1/2 flex flex-col bg-slate-50">
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
                    <div class="flex justify-between text-slate-600">
                        <span>Subtotal:</span>
                        <span id="subtotal">$0.00</span>
                    </div>
                    <div class="flex justify-between text-xl font-bold text-slate-900">
                        <span>Total:</span>
                        <span id="total">$0.00</span>
                    </div>
                </div>

                @if (!$isDemo)
                    <select id="paymentMethod" class="w-full mb-3 px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none">
                        <option value="efectivo">Efectivo</option>
                        <option value="tarjeta">Tarjeta</option>
                        <option value="transferencia">Transferencia</option>
                    </select>

                    <button id="checkoutBtn"
                            onclick="processCheckout()"
                            class="w-full py-3 bg-emerald-600 text-white font-medium rounded-lg hover:bg-emerald-700 disabled:bg-slate-300 disabled:cursor-not-allowed transition-colors flex items-center justify-center gap-2"
                            disabled>
                        <i class="bi bi-cash-coin"></i>
                        <span>Cobrar (F9)</span>
                    </button>
                @else
                    <div class="bg-amber-50 border border-amber-200 rounded-lg p-3 text-center">
                        <p class="text-sm text-amber-800 font-medium">
                            <i class="bi bi-lock mr-1"></i>
                            Modo visualización
                        </p>
                        <p class="text-xs text-amber-700 mt-1">Abre una caja para realizar ventas</p>
                        <a href="{{ route('cash-register.index') }}" class="inline-block mt-2 text-xs text-amber-600 hover:text-amber-700 underline">
                            Ir a Mi Caja
                        </a>
                    </div>
                @endif
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
<div id="toastContainer" class="fixed top-4 right-4 z-50 space-y-2"></div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Estado del carrito
    const cart = {
        items: [],

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
                document.getElementById('subtotal').textContent = '$0.00';
                document.getElementById('total').textContent = '$0.00';
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
                                class="text-red-500 hover:text-red-700 p-1">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <button onclick="cart.updateQuantity(${item.product_id}, ${item.quantity - (item.allows_decimals ? 0.5 : 1)})"
                                    class="w-8 h-8 bg-slate-200 rounded hover:bg-slate-300 flex items-center justify-center">
                                <i class="bi bi-dash"></i>
                            </button>
                            <input type="number"
                                   value="${item.quantity}"
                                   step="${item.allows_decimals ? '0.01' : '1'}"
                                   min="0.01"
                                   max="${item.stock}"
                                   class="w-20 text-center border border-slate-300 rounded py-1 focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none"
                                   onchange="cart.updateQuantity(${item.product_id}, parseFloat(this.value) || 0)">
                            <button onclick="cart.updateQuantity(${item.product_id}, ${item.quantity + (item.allows_decimals ? 0.5 : 1)})"
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
            document.getElementById('subtotal').textContent = '$' + total.toFixed(2);
            document.getElementById('total').textContent = '$' + total.toFixed(2);
            checkoutBtn.disabled = false;
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

    // Busqueda con debounce
    const searchInput = document.getElementById('searchInput');
    const allBranchesCheckbox = document.getElementById('allBranches');
    const searchResults = document.getElementById('searchResults');
    let debounceTimer;
    let lastResults = [];

    searchInput.addEventListener('input', function() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => searchProducts(), 300);
    });

    allBranchesCheckbox.addEventListener('change', () => {
        if (searchInput.value.trim()) searchProducts();
    });

    function searchProducts() {
        const query = searchInput.value.trim();
        if (query.length < 1) {
            searchResults.innerHTML = `
                <div class="text-center text-slate-500 py-8">
                    <i class="bi bi-search text-4xl text-slate-300 mb-3 block"></i>
                    <p>Escribe para buscar productos...</p>
                </div>
            `;
            lastResults = [];
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
            lastResults = data.products;
            renderSearchResults(data.products);
        })
        .catch(e => {
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
        if (products.length === 0) {
            searchResults.innerHTML = `
                <div class="text-center text-slate-500 py-8">
                    <i class="bi bi-search text-4xl text-slate-300 mb-3 block"></i>
                    <p>No se encontraron productos</p>
                </div>
            `;
            return;
        }

        searchResults.innerHTML = products.map(product => {
            const stockClass = {
                'out_of_stock': 'bg-red-100 text-red-700',
                'low_stock': 'bg-amber-100 text-amber-700',
                'in_stock': 'bg-emerald-100 text-emerald-700',
            }[product.stock_status];

            const isDisabled = product.stock <= 0;

            return `
                <div class="bg-white rounded-lg p-3 mb-2 shadow-sm border border-slate-200 ${isDisabled ? 'opacity-50' : 'hover:border-cyan-300 cursor-pointer'} transition-colors"
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
                                ${product.stock <= 0 ? 'Sin stock' : 'Stock: ' + product.stock}
                            </span>
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

    window.addProductFromSearch = function(productId) {
        const product = lastResults.find(p => p.id === productId);
        if (product) {
            cart.add(product);
            showToast(`${product.name} agregado`, 'success');
        }
    };

    // Enter: agregar si hay 1 resultado
    searchInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter' && lastResults.length >= 1) {
            e.preventDefault();
            const product = lastResults[0];
            if (product.stock > 0) {
                cart.add(product);
                showToast(`${product.name} agregado`, 'success');
                searchInput.value = '';
                lastResults = [];
                searchResults.innerHTML = `
                    <div class="text-center text-slate-500 py-8">
                        <i class="bi bi-search text-4xl text-slate-300 mb-3 block"></i>
                        <p>Escribe para buscar productos...</p>
                    </div>
                `;
            }
        }
    });

    // Checkout
    window.processCheckout = async function() {
        if (cart.items.length === 0) return;

        const checkoutBtn = document.getElementById('checkoutBtn');
        checkoutBtn.disabled = true;
        checkoutBtn.innerHTML = '<i class="bi bi-hourglass-split animate-pulse mr-2"></i>Procesando...';

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
                    payment_method: document.getElementById('paymentMethod').value,
                })
            });

            const data = await response.json();

            if (data.success) {
                showToast(`Venta #${data.sale.id} completada - Total: $${parseFloat(data.sale.total).toFixed(2)}`, 'success');
                cart.items = [];
                cart.render();
                searchInput.value = '';
                searchInput.focus();
            } else {
                showToast(data.message || 'Error al procesar la venta', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            showToast('Error de conexion', 'error');
        } finally {
            checkoutBtn.disabled = cart.items.length === 0;
            checkoutBtn.innerHTML = '<i class="bi bi-cash-coin"></i><span>Cobrar (F9)</span>';
        }
    };

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

        // Escape: Limpiar carrito (con confirmacion)
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
