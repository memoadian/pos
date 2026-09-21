// Vacia la outbox contra el servidor cuando hay conexion. Un solo envio a
// la vez, en orden (FIFO): si uno falla por red se detiene todo el lote (se
// reintentara despues); si el servidor lo rechaza (422) se marca ese
// registro como error y se sigue con los demas, para que uno malo no
// atore el resto de las ventas pendientes.

import * as outbox from './outbox.js';
import * as connection from './connection.js';

const CHECKOUT_URL = '/pos/checkout';

let syncing = false;
const listeners = new Set();

function csrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.content : '';
}

function emit(event) {
    listeners.forEach((fn) => {
        try {
            fn(event);
        } catch (e) {
            console.error('Error en listener de sync:', e);
        }
    });
}

export function onEvent(fn) {
    listeners.add(fn);
    return () => listeners.delete(fn);
}

export function isSyncing() {
    return syncing;
}

async function sendOne(record) {
    const response = await fetch(CHECKOUT_URL, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
            Accept: 'application/json',
        },
        credentials: 'same-origin',
        body: JSON.stringify({
            items: record.items,
            payment_method: record.payment_method,
            client_id: record.client_id || null,
            idempotency_key: record.idempotency_key,
            offline: true,
            sold_at: record.sold_at,
            offline_ref: record.offline_ref,
        }),
    });

    if (response.status === 401 || response.status === 419) {
        const error = new Error('Sesion expirada');
        error.code = 'AUTH';
        throw error;
    }

    const data = await response.json().catch(() => ({ success: false, message: 'Respuesta invalida del servidor' }));

    return { response, data };
}

// Intenta mandar todas las ventas pendientes (no las que ya quedaron en
// 'error', esas requieren revisarlas a mano con retryErrors()). Se detiene
// en el primer error de red (asume que se sigue sin conexion real, aunque
// el heartbeat diga lo contrario) pero sigue de largo ante rechazos del
// servidor.
export async function flush() {
    if (syncing) return { synced: 0, skipped: true };
    if (!connection.isOnline()) return { synced: 0, skipped: true };

    syncing = true;
    emit({ type: 'start' });
    let synced = 0;

    try {
        const pending = (await outbox.all()).filter((i) => i.status === 'pending');

        for (const record of pending) {
            try {
                const { data } = await sendOne(record);

                if (data.success) {
                    await outbox.remove(record.idempotency_key);
                    synced += 1;
                    emit({ type: 'synced', record, sale: data.sale });
                } else {
                    await outbox.update(record.idempotency_key, {
                        status: 'error',
                        error: data.message || 'El servidor rechazo la venta',
                        attempts: (record.attempts || 0) + 1,
                    });
                    emit({ type: 'rejected', record, message: data.message });
                }
            } catch (e) {
                if (e.code === 'AUTH') {
                    emit({ type: 'auth-required' });
                    break;
                }

                // Fallo de red a mitad del lote: se detiene aqui, el resto
                // se reintenta en el siguiente flush().
                emit({ type: 'network-error', record, error: e });
                break;
            }
        }
    } finally {
        syncing = false;
        emit({ type: 'done', synced });
    }

    return { synced, skipped: false };
}

export async function retryErrors() {
    const errored = (await outbox.all()).filter((i) => i.status === 'error');
    for (const record of errored) {
        await outbox.update(record.idempotency_key, { status: 'pending' });
    }
    return flush();
}

let interval = null;

export function start() {
    connection.onChange((online) => {
        if (online) flush();
    });

    if (interval) clearInterval(interval);
    interval = setInterval(async () => {
        const pending = await outbox.pendingCount();
        if (pending > 0) flush();
    }, 30000);

    // Intento inicial por si ya hay pendientes de una sesion anterior.
    flush();
}
