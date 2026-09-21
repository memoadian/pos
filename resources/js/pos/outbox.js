// Cola de ventas cobradas sin conexion, pendientes de mandar al servidor.
// Cada registro usa el mismo idempotency_key que ya evita ventas duplicadas
// en /pos/checkout (ver StoreSaleRequest / SaleService), asi que reintentar
// un envio es siempre seguro.

import * as db from './db.js';

let cache = null; // array en memoria, respaldado por IndexedDB

async function ensureLoaded() {
    if (cache === null) {
        cache = await db.getAll('outbox');
    }
    return cache;
}

export async function all() {
    return [...(await ensureLoaded())];
}

export async function pendingCount() {
    const items = await ensureLoaded();
    return items.filter((i) => i.status !== 'error').length;
}

export async function errorCount() {
    const items = await ensureLoaded();
    return items.filter((i) => i.status === 'error').length;
}

export async function add(sale) {
    const items = await ensureLoaded();
    const record = {
        status: 'pending',
        attempts: 0,
        error: null,
        created_at: Date.now(),
        ...sale,
    };
    items.push(record);
    await db.put('outbox', record);
    return record;
}

export async function update(idempotencyKey, patch) {
    const items = await ensureLoaded();
    const idx = items.findIndex((i) => i.idempotency_key === idempotencyKey);
    if (idx === -1) return null;

    const updated = { ...items[idx], ...patch };
    items[idx] = updated;
    await db.put('outbox', updated);
    return updated;
}

export async function remove(idempotencyKey) {
    const items = await ensureLoaded();
    cache = items.filter((i) => i.idempotency_key !== idempotencyKey);
    await db.remove('outbox', idempotencyKey);
}

export async function find(idempotencyKey) {
    const items = await ensureLoaded();
    return items.find((i) => i.idempotency_key === idempotencyKey) || null;
}
