// Envoltura minima sobre IndexedDB para el POS offline. Tres stores:
//   - products: snapshot del catalogo (id, barcode, nombre, precios, stock...)
//   - outbox:   ventas cobradas sin red, pendientes de enviar al servidor
//   - meta:     pares clave/valor sueltos (ultima sincronizacion, etc.)
//
// No se usa ninguna libreria: son pocas operaciones y asi no hay que empacar
// nada extra para que el Service Worker las sirva offline.

const DB_NAME = 'pos_offline';
const DB_VERSION = 1;

let dbPromise = null;

function openDb() {
    if (dbPromise) return dbPromise;

    dbPromise = new Promise((resolve, reject) => {
        if (!('indexedDB' in window)) {
            reject(new Error('IndexedDB no disponible en este navegador'));
            return;
        }

        const request = indexedDB.open(DB_NAME, DB_VERSION);

        request.onupgradeneeded = (event) => {
            const db = event.target.result;

            if (!db.objectStoreNames.contains('products')) {
                const store = db.createObjectStore('products', { keyPath: 'id' });
                store.createIndex('barcode', 'barcode', { unique: false });
            }

            if (!db.objectStoreNames.contains('outbox')) {
                // La idempotency_key ya es unica por venta (se genera con
                // crypto.randomUUID al abrir el carrito), sirve de key.
                db.createObjectStore('outbox', { keyPath: 'idempotency_key' });
            }

            if (!db.objectStoreNames.contains('meta')) {
                db.createObjectStore('meta', { keyPath: 'key' });
            }
        };

        request.onsuccess = () => resolve(request.result);
        request.onerror = () => reject(request.error);
    });

    return dbPromise;
}

function tx(storeName, mode) {
    return openDb().then((db) => db.transaction(storeName, mode).objectStore(storeName));
}

function requestToPromise(request) {
    return new Promise((resolve, reject) => {
        request.onsuccess = () => resolve(request.result);
        request.onerror = () => reject(request.error);
    });
}

export async function getAll(storeName) {
    const store = await tx(storeName, 'readonly');
    return requestToPromise(store.getAll());
}

export async function get(storeName, key) {
    const store = await tx(storeName, 'readonly');
    return requestToPromise(store.get(key));
}

export async function put(storeName, value) {
    const store = await tx(storeName, 'readwrite');
    return requestToPromise(store.put(value));
}

export async function remove(storeName, key) {
    const store = await tx(storeName, 'readwrite');
    return requestToPromise(store.delete(key));
}

export async function clear(storeName) {
    const store = await tx(storeName, 'readwrite');
    return requestToPromise(store.clear());
}

// Reemplaza todo el contenido de un store en una sola transaccion (usado
// para el refresh completo del catalogo, que siempre se manda entero).
export async function replaceAll(storeName, values) {
    const db = await openDb();
    return new Promise((resolve, reject) => {
        const transaction = db.transaction(storeName, 'readwrite');
        const store = transaction.objectStore(storeName);
        store.clear();
        values.forEach((value) => store.put(value));
        transaction.oncomplete = () => resolve();
        transaction.onerror = () => reject(transaction.error);
    });
}
