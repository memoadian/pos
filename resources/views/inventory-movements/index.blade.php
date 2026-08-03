@extends('layouts.app')
@section('title', 'Movimientos de Inventario')
@section('content')
    <div class="space-y-6">
        @include('components.alerts')

        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-semibold text-slate-900">Movimientos de Inventario</h1>
                <p class="text-sm text-slate-500 mt-1">Historial de entradas, salidas y ajustes</p>
            </div>
        </div>

        <!-- Selector de Sucursal -->
        <div class="bg-white rounded-lg border border-slate-200 p-4">
            <label class="block text-sm font-medium text-slate-700 mb-2">
                Sucursal <span class="text-red-500">*</span>
            </label>
            <select id="branchSelector" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500">
                <option value="">Seleccionar sucursal...</option>
                @foreach ($branches as $branch)
                    <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                @endforeach
            </select>
            <p class="text-xs text-amber-600 mt-2 hidden" id="branchLockWarning">
                <i class="bi bi-lock-fill"></i> Sucursal bloqueada. Guarda o cancela movimientos pendientes para cambiar.
            </p>
        </div>

        <!-- Botones de Acción -->
        <div class="flex gap-2">
            <button id="openModalBtn" disabled type="button" class="inline-flex items-center gap-2 px-4 py-2 bg-cyan-600 hover:bg-cyan-700 text-white text-sm font-medium rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                <i class="bi bi-plus-lg"></i><span>Nuevo Movimiento</span>
            </button>
            <button id="toggleInlineBtn" disabled type="button" class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                <i class="bi bi-list-ul"></i><span>Registro Múltiple</span>
            </button>
        </div>

        <!-- Sección Inline Rows (Inicialmente Oculta) -->
        <div id="inlineSection" class="hidden bg-white rounded-lg border border-slate-200 p-4">
            <div class="flex justify-between items-center mb-4">
                <h3 class="font-semibold text-slate-900">Registro Múltiple de Movimientos</h3>
                <button id="closeInlineBtn" type="button" class="text-slate-500 hover:text-slate-700">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 border-b border-slate-200">
                        <tr>
                            <th class="px-3 py-2 text-left font-medium text-slate-700">Producto</th>
                            <th class="px-3 py-2 text-left font-medium text-slate-700 w-32">Tipo</th>
                            <th class="px-3 py-2 text-left font-medium text-slate-700 w-32">Cantidad</th>
                            <th class="px-3 py-2 text-left font-medium text-slate-700">Motivo</th>
                            <th class="px-3 py-2 w-12"></th>
                        </tr>
                    </thead>
                    <tbody id="inlineRows" class="divide-y divide-slate-200"></tbody>
                </table>
            </div>

            <div class="flex justify-between items-center mt-4 pt-4 border-t border-slate-200">
                <button id="addRowBtn" type="button" class="inline-flex items-center gap-1 px-3 py-2 text-sm bg-slate-100 hover:bg-slate-200 rounded-lg transition-colors">
                    <i class="bi bi-plus"></i> Agregar Fila
                </button>
                <div class="flex gap-2">
                    <button id="cancelInlineBtn" type="button" class="px-4 py-2 bg-slate-300 hover:bg-slate-400 text-slate-900 rounded-lg transition-colors font-medium">
                        Cancelar
                    </button>
                    <button id="saveAllBtn" type="button" class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg transition-colors font-medium">
                        <i class="bi bi-check-lg"></i> Guardar Todos
                    </button>
                </div>
            </div>
        </div>

        <!-- Filtros de Búsqueda -->
        <div class="bg-white rounded-lg border border-slate-200 p-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                <select class="px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500" id="branchFilter">
                    <option value="">Todas sucursales</option>
                    @foreach ($branches as $b)
                        <option value="{{ $b->id }}">{{ $b->name }}</option>
                    @endforeach
                </select>
                <select class="px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500" id="typeFilter">
                    <option value="">Todos tipos</option>
                    <option value="IN">Entrada</option>
                    <option value="OUT">Salida</option>
                    <option value="ADJUST">Ajuste</option>
                </select>
                <input class="px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500" id="dateFromFilter" type="date">
                <input class="px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500" id="dateToFilter" type="date">
            </div>
        </div>

        <!-- Tabla de Movimientos -->
        <div class="bg-white rounded-lg border border-slate-200 overflow-hidden">
            <table class="w-full">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-600 uppercase">Fecha</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-600 uppercase">Tipo</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-600 uppercase">Producto</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-600 uppercase">Sucursal</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-600 uppercase">Cantidad</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-600 uppercase">Motivo</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-600 uppercase">Usuario</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200" id="movementsTable">@include('inventory-movements.partials.table-rows')</tbody>
            </table>
        </div>
        @if ($movements->hasPages())
            <div class="flex justify-center">{{ $movements->links() }}</div>
        @endif
    </div>

    <!-- Modal Nuevo Movimiento -->
    <div id="movementModal" class="hidden fixed inset-0 bg-black/80 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="movementModalTitle">
        <div id="modalScrollContainer" class="bg-white rounded-lg p-6 w-full max-w-md max-h-[85vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-4">
                <h2 id="movementModalTitle" class="text-xl font-bold text-slate-900">Nuevo Movimiento</h2>
                <button id="closeModalBtn" type="button" class="text-slate-500 hover:text-slate-700 text-2xl leading-none">&times;</button>
            </div>

            <form id="movementForm" class="space-y-4">
                @csrf

                <!-- Autocomplete Producto -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">
                        Producto <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <input type="text" id="modalProductSearch" autocomplete="off"
                            placeholder="Buscar por nombre o código..."
                            class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500">
                        <input type="hidden" id="modalProductId" name="product_id">
                        <div id="modalProductResults" class="z-[60] bg-white border border-slate-300 rounded-lg shadow-lg hidden max-h-60 overflow-y-auto">
                        </div>
                    </div>
                    <p class="text-xs text-slate-500 mt-1 hidden" id="modalStockInfo"></p>
                    <span class="text-red-500 text-xs mt-1 block" id="modalProductError"></span>
                </div>

                <!-- Tipo -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">
                        Tipo <span class="text-red-500">*</span>
                    </label>
                    <select name="type" id="modalType" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500">
                        <option value="">Seleccionar...</option>
                        <option value="IN">Entrada (IN)</option>
                        <option value="OUT">Salida (OUT)</option>
                        <option value="ADJUST">Ajuste (ADJUST)</option>
                    </select>
                    <span class="text-red-500 text-xs mt-1 block" id="modalTypeError"></span>
                </div>

                <!-- Cantidad -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1" id="modalQuantityLabel">
                        Cantidad <span class="text-red-500">*</span>
                    </label>
                    <input type="number" name="quantity" id="modalQuantity" required step="0.01" min="0.01"
                        class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500">
                    <p class="text-xs mt-1 hidden" id="modalQuantityHint"></p>
                    <span class="text-red-500 text-xs mt-1 block" id="modalQuantityError"></span>
                </div>

                <!-- Motivo (Opcional) -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Motivo</label>
                    <textarea name="reason" id="modalReason" rows="2"
                        class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500"></textarea>
                    <span class="text-red-500 text-xs mt-1 block" id="modalReasonError"></span>
                </div>

                <div class="flex gap-2 justify-end pt-4 border-t border-slate-200">
                    <button type="button" id="cancelModalBtn" class="px-4 py-2 bg-slate-300 hover:bg-slate-400 text-slate-900 rounded-lg transition-colors font-medium">
                        Cerrar
                    </button>
                    <button type="submit" class="px-4 py-2 bg-cyan-600 hover:bg-cyan-700 text-white rounded-lg transition-colors font-medium">
                        Guardar y agregar otro
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        // ==================== Estado Global ====================
        const state = {
            branchId: null,
            inlineMode: false,
            inlineRows: [],
            rowIdCounter: 0,
            selectedProducts: new Map(),
            searchDebounce: null,
            modalStock: null,
            dropdowns: {}, // key: 'modal' o rowId -> { products, highlightIndex }
        };

        // ==================== Elementos del DOM ====================
        const elements = {
            branchSelector: document.getElementById('branchSelector'),
            branchLockWarning: document.getElementById('branchLockWarning'),
            openModalBtn: document.getElementById('openModalBtn'),
            toggleInlineBtn: document.getElementById('toggleInlineBtn'),

            // Modal
            movementModal: document.getElementById('movementModal'),
            movementForm: document.getElementById('movementForm'),
            closeModalBtn: document.getElementById('closeModalBtn'),
            cancelModalBtn: document.getElementById('cancelModalBtn'),
            modalProductSearch: document.getElementById('modalProductSearch'),
            modalProductId: document.getElementById('modalProductId'),
            modalProductResults: document.getElementById('modalProductResults'),
            modalType: document.getElementById('modalType'),
            modalQuantity: document.getElementById('modalQuantity'),
            modalQuantityHint: document.getElementById('modalQuantityHint'),
            modalStockInfo: document.getElementById('modalStockInfo'),
            modalReason: document.getElementById('modalReason'),

            // Inline
            inlineSection: document.getElementById('inlineSection'),
            closeInlineBtn: document.getElementById('closeInlineBtn'),
            addRowBtn: document.getElementById('addRowBtn'),
            cancelInlineBtn: document.getElementById('cancelInlineBtn'),
            saveAllBtn: document.getElementById('saveAllBtn'),
            inlineRows: document.getElementById('inlineRows'),

            // Tabla y filtros
            movementsTable: document.getElementById('movementsTable'),
            branchFilter: document.getElementById('branchFilter'),
            typeFilter: document.getElementById('typeFilter'),
            dateFromFilter: document.getElementById('dateFromFilter'),
            dateToFilter: document.getElementById('dateToFilter'),
        };

        // ==================== Inicialización ====================
        function init() {
            setupBranchSelector();
            autoSelectSingleBranch();
            setupModalEvents();
            setupInlineEvents();
            setupFilterEvents();

            // Los dropdowns de producto usan position:fixed; si la página o el
            // modal hacen scroll/resize, se ocultan en vez de quedar desalineados.
            window.addEventListener('scroll', hideAllDropdowns, true);
            window.addEventListener('resize', hideAllDropdowns);

            // Cerrar el dropdown al hacer clic fuera de él
            document.addEventListener('click', (e) => {
                if (!e.target.closest('.inline-product-search, .inline-product-results, #modalProductSearch, #modalProductResults')) {
                    hideAllDropdowns();
                }
            });
        }

        // ==================== Selector de Sucursal ====================
        function autoSelectSingleBranch() {
            // Si solo hay una sucursal activa, seleccionarla automáticamente
            // para no obligar a un clic extra antes de poder registrar movimientos.
            const options = elements.branchSelector.querySelectorAll('option[value]:not([value=""])');
            if (options.length === 1) {
                elements.branchSelector.value = options[0].value;
                state.branchId = options[0].value;
                updateButtonStates();
            }
        }

        function setupBranchSelector() {
            elements.branchSelector.addEventListener('change', (e) => {
                const newBranchId = e.target.value;

                if (state.inlineMode && state.inlineRows.length > 0) {
                    // Advertencia si hay movimientos pendientes
                    showWarning('Tienes movimientos pendientes. Guarda o cancela antes de cambiar de sucursal.');
                    elements.branchSelector.value = state.branchId || '';
                    return;
                }

                state.branchId = newBranchId;
                updateButtonStates();
                showHideBranchLock();
            });
        }

        function updateButtonStates() {
            const hasSelection = !!state.branchId;
            elements.openModalBtn.disabled = !hasSelection;
            elements.toggleInlineBtn.disabled = !hasSelection;
        }

        function showHideBranchLock() {
            if (state.inlineMode && state.inlineRows.length > 0) {
                elements.branchSelector.disabled = true;
                elements.branchLockWarning.classList.remove('hidden');
            } else {
                elements.branchSelector.disabled = false;
                elements.branchLockWarning.classList.add('hidden');
            }
        }

        // ==================== Modal Events ====================
        function setupModalEvents() {
            // Abrir modal
            elements.openModalBtn.addEventListener('click', openModal);

            // Cerrar modal
            elements.closeModalBtn.addEventListener('click', closeModal);
            elements.cancelModalBtn.addEventListener('click', closeModal);
            elements.movementModal.addEventListener('click', (e) => {
                if (e.target === elements.movementModal) closeModal();
            });

            // Escape cierra el modal (si el dropdown de producto ya está cerrado)
            elements.movementModal.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') closeModal();
            });

            // Autocomplete de producto en modal
            elements.modalProductSearch.addEventListener('input', debounce(() => {
                searchProducts(elements.modalProductSearch.value, 'modal');
            }, 300));
            elements.modalProductSearch.addEventListener('keydown', (e) => handleSearchKeydown(e, 'modal'));

            // Tipo -> actualizar hint de cantidad (ADJUST reemplaza el stock, no lo suma/resta)
            elements.modalType.addEventListener('change', updateModalQuantityHint);

            // Submit del formulario
            elements.movementForm.addEventListener('submit', submitModalForm);
        }

        function openModal() {
            elements.movementModal.classList.remove('hidden');
            resetModalForm();
            elements.modalProductSearch.focus();
        }

        function closeModal() {
            elements.movementModal.classList.add('hidden');
            resetModalForm();
        }

        function resetModalForm() {
            elements.movementForm.reset();
            elements.modalProductId.value = '';
            elements.modalProductResults.classList.add('hidden');
            elements.modalStockInfo.classList.add('hidden');
            state.modalStock = null;
            updateModalQuantityHint();
            clearErrors('modal');
        }

        function updateModalQuantityHint() {
            const type = elements.modalType.value;

            if (type === 'ADJUST') {
                elements.modalQuantityHint.textContent = state.modalStock !== null
                    ? `⚠️ Reemplaza el stock total (actual: ${state.modalStock}), no se suma ni se resta`
                    : '⚠️ Reemplaza el stock total, no se suma ni se resta';
                elements.modalQuantityHint.className = 'text-xs mt-1 text-amber-600 font-medium';
                elements.modalQuantityHint.classList.remove('hidden');
                elements.modalQuantity.classList.add('bg-amber-50');

                if (!elements.modalQuantity.value && state.modalStock !== null) {
                    elements.modalQuantity.value = state.modalStock;
                }
            } else {
                elements.modalQuantityHint.classList.add('hidden');
                elements.modalQuantity.classList.remove('bg-amber-50');
            }
        }

        async function submitModalForm(e) {
            e.preventDefault();

            if (!state.branchId) {
                showWarning('Selecciona una sucursal primero');
                return;
            }

            if (!elements.modalProductId.value) {
                setError('modalProductError', 'Selecciona un producto');
                return;
            }

            const formData = new FormData(elements.movementForm);
            formData.append('branch_id', state.branchId);

            try {
                setSubmitLoading(true);
                const response = await fetch('{{ route("inventory-movements.store") }}', {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    showSuccess(data.message);
                    // No cerramos el modal: se queda listo para registrar el siguiente movimiento.
                    resetModalForm();
                    elements.modalProductSearch.focus();
                    await reloadTable();
                } else {
                    showError(data.message || 'Error desconocido');
                }
            } catch (error) {
                console.error('Error:', error);
                showError('Error al procesar la solicitud');
            } finally {
                setSubmitLoading(false);
            }
        }

        // ==================== Inline Rows ====================
        function setupInlineEvents() {
            elements.toggleInlineBtn.addEventListener('click', toggleInlineMode);
            elements.closeInlineBtn.addEventListener('click', closeInlineMode);
            elements.cancelInlineBtn.addEventListener('click', closeInlineMode);
            elements.addRowBtn.addEventListener('click', addInlineRow);
            elements.saveAllBtn.addEventListener('click', submitInlineForm);
        }

        function toggleInlineMode() {
            if (state.inlineMode) {
                closeInlineMode();
            } else {
                state.inlineMode = true;
                state.inlineRows = [];
                state.rowIdCounter = 0;
                elements.inlineSection.classList.remove('hidden');
                showHideBranchLock();
                addInlineRow();
            }
        }

        function closeInlineMode() {
            state.inlineMode = false;
            state.inlineRows = [];
            elements.inlineSection.classList.add('hidden');
            elements.inlineRows.innerHTML = '';
            showHideBranchLock();
        }

        function addInlineRow() {
            const rowId = state.rowIdCounter++;
            state.inlineRows.push({id: rowId, product: null});

            const row = document.createElement('tr');
            row.id = `inline-row-${rowId}`;
            row.className = 'hover:bg-slate-50';
            row.innerHTML = `
                <td class="px-3 py-3">
                    <div class="relative">
                        <input type="text" class="inline-product-search w-full px-2 py-1 border border-slate-300 rounded text-sm"
                            placeholder="Buscar..." autocomplete="off" data-row-id="${rowId}">
                        <input type="hidden" class="inline-product-id" value="" data-row-id="${rowId}">
                        <input type="hidden" class="inline-product-stock" value="" data-row-id="${rowId}">
                        <div class="inline-product-results z-[60] bg-white border border-slate-300 rounded shadow-lg hidden max-h-40 overflow-y-auto" data-row-id="${rowId}"></div>
                    </div>
                    <p class="inline-stock-info text-[11px] text-slate-400 mt-0.5" data-row-id="${rowId}"></p>
                </td>
                <td class="px-3 py-3">
                    <select class="inline-type w-full px-2 py-1 border border-slate-300 rounded text-sm" data-row-id="${rowId}">
                        <option value="">Tipo</option>
                        <option value="IN">Entrada</option>
                        <option value="OUT">Salida</option>
                        <option value="ADJUST">Ajuste</option>
                    </select>
                </td>
                <td class="px-3 py-3">
                    <input type="number" class="inline-quantity w-full px-2 py-1 border border-slate-300 rounded text-sm"
                        placeholder="0.00" step="0.01" min="0.01" data-row-id="${rowId}">
                    <p class="inline-quantity-hint text-[11px] mt-0.5 hidden" data-row-id="${rowId}"></p>
                </td>
                <td class="px-3 py-3">
                    <input type="text" class="inline-reason w-full px-2 py-1 border border-slate-300 rounded text-sm"
                        placeholder="Motivo (opcional)" data-row-id="${rowId}">
                </td>
                <td class="px-3 py-3 text-center">
                    <button type="button" class="inline-delete text-red-600 hover:text-red-800" data-row-id="${rowId}">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            `;

            elements.inlineRows.appendChild(row);

            // Setupear evento de búsqueda
            const searchInput = row.querySelector('.inline-product-search');
            searchInput.addEventListener('input', debounce(() => {
                searchProducts(searchInput.value, 'inline', rowId);
            }, 300));
            searchInput.addEventListener('keydown', (e) => handleSearchKeydown(e, 'inline', rowId));

            // Tipo -> actualizar hint de cantidad (ADJUST reemplaza el stock, no lo suma/resta)
            row.querySelector('.inline-type').addEventListener('change', () => {
                updateInlineQuantityHint(rowId);
            });

            // Setup delete button
            row.querySelector('.inline-delete').addEventListener('click', () => {
                deleteInlineRow(rowId);
            });
        }

        function deleteInlineRow(rowId) {
            state.inlineRows = state.inlineRows.filter(r => r.id !== rowId);
            document.getElementById(`inline-row-${rowId}`).remove();
        }

        function updateInlineQuantityHint(rowId) {
            const row = document.getElementById(`inline-row-${rowId}`);
            if (!row) return;

            const type = row.querySelector('.inline-type').value;
            const hint = row.querySelector('.inline-quantity-hint');
            const quantityInput = row.querySelector('.inline-quantity');
            const stock = row.querySelector('.inline-product-stock').value;

            if (type === 'ADJUST') {
                hint.textContent = stock !== ''
                    ? `Reemplaza el stock total (actual: ${stock})`
                    : `Reemplaza el stock total`;
                hint.className = 'inline-quantity-hint text-[11px] mt-0.5 text-amber-600 font-medium';
                hint.classList.remove('hidden');
                quantityInput.classList.add('bg-amber-50');
                if (!quantityInput.value && stock !== '') {
                    quantityInput.value = stock;
                }
            } else {
                hint.classList.add('hidden');
                quantityInput.classList.remove('bg-amber-50');
            }
        }

        async function submitInlineForm() {
            if (!state.branchId) {
                showWarning('Selecciona una sucursal primero');
                return;
            }

            // Validar filas
            const movements = [];
            let hasErrors = false;

            document.querySelectorAll('[id^="inline-row-"]').forEach((row, index) => {
                const productId = row.querySelector('.inline-product-id').value;
                const type = row.querySelector('.inline-type').value;
                const quantity = row.querySelector('.inline-quantity').value;
                const reason = row.querySelector('.inline-reason').value;

                if (!productId || !type || !quantity) {
                    hasErrors = true;
                    return;
                }

                movements.push({
                    product_id: productId,
                    type: type,
                    quantity: parseFloat(quantity),
                    reason: reason || null
                });
            });

            if (hasErrors) {
                showWarning('Completa todos los campos requeridos en cada fila');
                return;
            }

            if (movements.length === 0) {
                showWarning('Agrega al menos una fila');
                return;
            }

            const formData = new FormData();
            formData.append('_token', document.querySelector('[name="_token"]').value);
            formData.append('branch_id', state.branchId);
            movements.forEach((mov, index) => {
                formData.append(`movements[${index}][product_id]`, mov.product_id);
                formData.append(`movements[${index}][type]`, mov.type);
                formData.append(`movements[${index}][quantity]`, mov.quantity);
                formData.append(`movements[${index}][reason]`, mov.reason);
            });

            try {
                setSubmitLoading(true);
                const response = await fetch('{{ route("inventory-movements.store") }}', {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    showSuccess(data.message);
                    closeInlineMode();
                    await reloadTable();
                } else {
                    showError(data.message || 'Error desconocido');
                }
            } catch (error) {
                console.error('Error:', error);
                showError('Error al procesar la solicitud');
            } finally {
                setSubmitLoading(false);
            }
        }

        // ==================== Autocomplete ====================
        async function searchProducts(query, context, rowId = null) {
            if (!query || !state.branchId) return;

            const resultsElement = context === 'modal'
                ? elements.modalProductResults
                : document.querySelector(`[data-row-id="${rowId}"].inline-product-results`);

            if (!resultsElement) return;

            try {
                const response = await fetch(
                    `{{ route('inventory-movements.products.search') }}?query=${encodeURIComponent(query)}&branch_id=${state.branchId}`,
                    {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                        }
                    }
                );

                const data = await response.json();
                renderProductResults(data.products, context, rowId);
            } catch (error) {
                console.error('Error searching products:', error);
            }
        }

        // El dropdown se posiciona con position:fixed calculado en JS (en vez de
        // absolute dentro de un contenedor con overflow-y-auto) para que no se
        // recorte cuando el modal o la fila hacen scroll.
        function positionDropdown(anchorEl, dropdownEl) {
            if (!anchorEl) return;
            const rect = anchorEl.getBoundingClientRect();
            dropdownEl.style.position = 'fixed';
            dropdownEl.style.top = `${rect.bottom + 4}px`;
            dropdownEl.style.left = `${rect.left}px`;
            dropdownEl.style.width = `${rect.width}px`;
        }

        function hideAllDropdowns() {
            elements.modalProductResults.classList.add('hidden');
            document.querySelectorAll('.inline-product-results').forEach(el => el.classList.add('hidden'));
        }

        function renderProductResults(products, context, rowId = null) {
            const resultsElement = context === 'modal'
                ? elements.modalProductResults
                : document.querySelector(`[data-row-id="${rowId}"].inline-product-results`);
            const anchorEl = context === 'modal'
                ? elements.modalProductSearch
                : document.querySelector(`[data-row-id="${rowId}"].inline-product-search`);

            if (!resultsElement) return;

            const key = context === 'modal' ? 'modal' : rowId;
            state.dropdowns[key] = { products, highlightIndex: -1 };

            if (products.length === 0) {
                resultsElement.innerHTML = '<div class="px-3 py-3 text-sm text-slate-500 text-center">No se encontraron productos</div>';
            } else {
                resultsElement.innerHTML = products.map((product, index) => `
                    <div class="product-result-item px-3 py-2 hover:bg-slate-100 cursor-pointer border-b border-slate-100 last:border-0"
                        data-index="${index}"
                        onmouseenter="highlightResult('${key}', ${index})"
                        onclick="selectProduct(${product.id}, '${escapeHtml(product.name)}', '${escapeHtml(product.barcode || '')}', '${context}', ${rowId}, ${product.stock})">
                        <div class="font-medium text-slate-900">${escapeHtml(product.name)}</div>
                        <div class="text-xs text-slate-500">${product.barcode ? escapeHtml(product.barcode) + ' | ' : ''}Stock: ${product.stock}</div>
                    </div>
                `).join('');
            }

            positionDropdown(anchorEl, resultsElement);
            resultsElement.classList.remove('hidden');
        }

        function highlightResult(key, index) {
            const dd = state.dropdowns[key];
            if (!dd) return;
            dd.highlightIndex = index;
            const resultsElement = key === 'modal'
                ? elements.modalProductResults
                : document.querySelector(`[data-row-id="${key}"].inline-product-results`);
            applyHighlight(resultsElement, index);
        }

        function applyHighlight(resultsElement, index) {
            if (!resultsElement) return;
            resultsElement.querySelectorAll('.product-result-item').forEach((el, i) => {
                el.classList.toggle('bg-cyan-50', i === index);
            });
            const activeEl = resultsElement.querySelector(`.product-result-item[data-index="${index}"]`);
            if (activeEl) activeEl.scrollIntoView({ block: 'nearest' });
        }

        function handleSearchKeydown(e, context, rowId = null) {
            const key = context === 'modal' ? 'modal' : rowId;
            const resultsElement = context === 'modal'
                ? elements.modalProductResults
                : document.querySelector(`[data-row-id="${rowId}"].inline-product-results`);
            const dd = state.dropdowns[key];

            if (!resultsElement || resultsElement.classList.contains('hidden') || !dd || dd.products.length === 0) {
                return;
            }

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                dd.highlightIndex = Math.min(dd.highlightIndex + 1, dd.products.length - 1);
                applyHighlight(resultsElement, dd.highlightIndex);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                dd.highlightIndex = Math.max(dd.highlightIndex - 1, 0);
                applyHighlight(resultsElement, dd.highlightIndex);
            } else if (e.key === 'Enter') {
                e.preventDefault();
                const idx = dd.highlightIndex >= 0 ? dd.highlightIndex : 0;
                const product = dd.products[idx];
                selectProduct(product.id, product.name, product.barcode || '', context, rowId, product.stock);
            } else if (e.key === 'Escape') {
                resultsElement.classList.add('hidden');
                e.stopPropagation();
            }
        }

        function selectProduct(id, name, barcode, context, rowId = null, stock = 0) {
            if (context === 'modal') {
                elements.modalProductId.value = id;
                elements.modalProductSearch.value = `${name}${barcode ? ' (' + barcode + ')' : ''}`;
                elements.modalProductResults.classList.add('hidden');
                state.modalStock = stock;
                elements.modalStockInfo.textContent = `Stock actual: ${stock}`;
                elements.modalStockInfo.classList.remove('hidden');
                updateModalQuantityHint();
                clearErrors('modal');
            } else {
                const row = document.getElementById(`inline-row-${rowId}`);
                row.querySelector('.inline-product-id').value = id;
                row.querySelector('.inline-product-search').value = `${name}${barcode ? ' (' + barcode + ')' : ''}`;
                row.querySelector('.inline-product-stock').value = stock;
                row.querySelector('.inline-product-results').classList.add('hidden');
                row.querySelector('.inline-stock-info').textContent = `Stock actual: ${stock}`;
                updateInlineQuantityHint(rowId);
            }
        }

        // ==================== Filtros ====================
        function setupFilterEvents() {
            [elements.branchFilter, elements.typeFilter, elements.dateFromFilter, elements.dateToFilter]
                .forEach(el => el.addEventListener('change', filter));
        }

        async function filter() {
            const url = new URL('{{ route("inventory-movements.index") }}');
            if (elements.branchFilter.value) url.searchParams.append('branch', elements.branchFilter.value);
            if (elements.typeFilter.value) url.searchParams.append('type', elements.typeFilter.value);
            if (elements.dateFromFilter.value) url.searchParams.append('date_from', elements.dateFromFilter.value);
            if (elements.dateToFilter.value) url.searchParams.append('date_to', elements.dateToFilter.value);

            try {
                const response = await fetch(url, {
                    headers: {'X-Requested-With': 'XMLHttpRequest'}
                });
                const html = await response.text();
                elements.movementsTable.innerHTML = html;
            } catch (error) {
                console.error('Error filtering:', error);
            }
        }

        // ==================== Utilidades ====================
        async function reloadTable() {
            await filter();
        }

        function debounce(func, wait) {
            return function(...args) {
                clearTimeout(state.searchDebounce);
                state.searchDebounce = setTimeout(() => func(...args), wait);
            };
        }

        function showSuccess(message) {
            showNotification(message, 'success');
        }

        function showError(message) {
            showNotification(message, 'error');
        }

        function showWarning(message) {
            showNotification(message, 'warning');
        }

        function showNotification(message, type) {
            const bgColor = type === 'success' ? 'bg-emerald-50 border-emerald-200' :
                          type === 'error' ? 'bg-red-50 border-red-200' :
                          'bg-amber-50 border-amber-200';
            const textColor = type === 'success' ? 'text-emerald-700' :
                            type === 'error' ? 'text-red-700' :
                            'text-amber-700';
            const icon = type === 'success' ? 'bi-check-circle' :
                       type === 'error' ? 'bi-exclamation-circle' :
                       'bi-exclamation-triangle';

            const alert = document.createElement('div');
            alert.className = `fixed top-4 right-4 ${bgColor} border rounded-lg p-4 flex items-gap-3 shadow-lg z-40 max-w-md`;
            alert.innerHTML = `
                <i class="bi ${icon} ${textColor} text-lg flex-shrink-0"></i>
                <span class="${textColor}">${escapeHtml(message)}</span>
            `;
            document.body.appendChild(alert);
            setTimeout(() => alert.remove(), 4000);
        }

        function setError(fieldId, message) {
            const element = document.getElementById(fieldId);
            if (element) element.textContent = message;
        }

        function clearErrors(context) {
            const selector = context === 'modal' ? '#movementForm [id$="Error"]' : '';
            if (selector) {
                document.querySelectorAll(selector).forEach(el => el.textContent = '');
            }
        }

        function setSubmitLoading(loading) {
            const buttons = [
                elements.movementForm.querySelector('button[type="submit"]'),
                elements.saveAllBtn
            ];
            buttons.forEach(btn => {
                if (btn) btn.disabled = loading;
            });
        }

        function escapeHtml(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, m => map[m]);
        }

        // ==================== Inicializar ====================
        init();
    </script>
@endsection
