<div id="confirmModal" class="fixed inset-0 z-[90] hidden">
    <div class="absolute inset-0 bg-black/50" onclick="ConfirmModal.cancel()"></div>
    <div class="relative flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-sm w-full">
            <div class="p-6">
                <div class="flex items-start gap-4">
                    <div id="confirmModalIcon" class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center flex-shrink-0">
                        <i class="bi bi-exclamation-triangle text-red-600 text-lg"></i>
                    </div>
                    <div class="min-w-0">
                        <h3 id="confirmModalTitle" class="text-base font-semibold text-slate-900">Confirmar acción</h3>
                        <p id="confirmModalMessage" class="mt-1 text-sm text-slate-500">¿Estás seguro?</p>
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-3 px-6 py-4 bg-slate-50 border-t border-slate-200 rounded-b-lg">
                <button type="button" id="confirmModalCancel" class="flex-1 px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-medium rounded-lg transition-colors">Cancelar</button>
                <button type="button" id="confirmModalConfirm" class="flex-1 px-4 py-2.5 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition-colors">Confirmar</button>
            </div>
        </div>
    </div>
</div>
<script>
const ConfirmModal = (function () {
    const modal = document.getElementById('confirmModal');
    const iconWrap = document.getElementById('confirmModalIcon');
    const icon = iconWrap.querySelector('i');
    const titleEl = document.getElementById('confirmModalTitle');
    const messageEl = document.getElementById('confirmModalMessage');
    const cancelBtn = document.getElementById('confirmModalCancel');
    const confirmBtn = document.getElementById('confirmModalConfirm');
    let onConfirmCallback = null;

    function close() {
        modal.classList.add('hidden');
        onConfirmCallback = null;
    }

    function isOpen() {
        return ! modal.classList.contains('hidden');
    }

    /**
     * Laravel no acepta DELETE/PUT desde un link ni desde fetch sin token, asi que
     * el borrado se hace con un form POST + _method que se arma y se manda al vuelo.
     */
    function submitMethod(action, method) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = action;
        form.innerHTML = `
            <input type="hidden" name="_token" value="${document.querySelector('meta[name="csrf-token"]').content}">
            <input type="hidden" name="_method" value="${method}">
        `;

        document.body.appendChild(form);
        form.submit();
    }

    cancelBtn.addEventListener('click', close);
    // El listener va en document (no en el modal) para que Escape cierre aunque el
    // foco se haya movido fuera; las pantallas con su propio Escape usan isOpen().
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && isOpen()) close(); });
    confirmBtn.addEventListener('click', () => {
        const callback = onConfirmCallback;
        close();
        if (callback) callback();
    });

    return {
        show({
            title = 'Confirmar acción',
            message = '¿Estás seguro?',
            confirmText = 'Confirmar',
            cancelText = 'Cancelar',
            danger = true,
            onConfirm,
        }) {
            titleEl.textContent = title;
            messageEl.textContent = message;
            confirmBtn.textContent = confirmText;
            cancelBtn.textContent = cancelText;

            iconWrap.classList.toggle('bg-red-100', danger);
            iconWrap.classList.toggle('bg-cyan-100', !danger);
            icon.classList.toggle('text-red-600', danger);
            icon.classList.toggle('text-cyan-600', !danger);
            confirmBtn.classList.toggle('bg-red-600', danger);
            confirmBtn.classList.toggle('hover:bg-red-700', danger);
            confirmBtn.classList.toggle('bg-cyan-600', !danger);
            confirmBtn.classList.toggle('hover:bg-cyan-700', !danger);

            onConfirmCallback = onConfirm;
            modal.classList.remove('hidden');
            confirmBtn.focus();
        },
        /**
         * Atajo para el caso mas repetido: confirmar y mandar un DELETE al backend.
         */
        confirmDelete({ action, title = 'Eliminar', message, confirmText = 'Eliminar', method = 'DELETE' }) {
            this.show({
                title,
                message,
                confirmText,
                danger: true,
                onConfirm: () => submitMethod(action, method),
            });
        },
        cancel: close,
        isOpen,
    };
})();
</script>
