// Catalogo local del POS: copia en memoria (respaldada en IndexedDB) de
// todos los productos activos, con precios y stock ya resueltos para la
// sucursal en curso. La busqueda del POS siempre pega aqui, con o sin
// internet, para que el comportamiento sea identico en ambos casos.

import * as db from './db.js';

const CATALOG_URL = '/pos/catalog';

let cache = [];
let generatedAt = null;
let loaded = false;

function normalizeTerm(value) {
    return (value || '').toString().trim().toLowerCase();
}

// Carga lo que haya en IndexedDB (de una sesion anterior) a memoria. Se
// llama una vez al iniciar el POS, antes de que termine el refresh contra
// el servidor, para que la busqueda funcione de inmediato si se abre sin red.
export async function loadFromDb() {
    if (loaded) return cache;

    try {
        cache = await db.getAll('products');
        const meta = await db.get('meta', 'catalog');
        generatedAt = meta ? meta.generatedAt : null;
    } catch (e) {
        console.error('No se pudo leer el catalogo local:', e);
        cache = [];
    }

    loaded = true;
    return cache;
}

// Descarga el catalogo completo del servidor y reemplaza tanto la memoria
// como IndexedDB. Se usa al entrar al POS y cada cierto tiempo si hay red.
// Nunca lanza: si falla, se sigue usando lo que ya estaba cargado.
export async function refresh() {
    try {
        const response = await fetch(CATALOG_URL, {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
            cache: 'no-store',
        });

        if (!response.ok) throw new Error(`HTTP ${response.status}`);

        const data = await response.json();
        if (!data.success) throw new Error(data.message || 'Respuesta invalida');

        cache = data.products;
        generatedAt = data.generated_at;
        loaded = true;

        await db.replaceAll('products', cache);
        await db.put('meta', { key: 'catalog', generatedAt, count: cache.length });

        return { ok: true, count: cache.length, generatedAt };
    } catch (e) {
        console.error('No se pudo actualizar el catalogo:', e);
        return { ok: false, error: e };
    }
}

export function generatedAtLabel() {
    return generatedAt;
}

export function isLoaded() {
    return loaded && cache.length > 0;
}

export function getById(productId) {
    return cache.find((p) => p.id === productId) || null;
}

// Replica Product::scopeSearch (nombre, codigo de barras o alias, con LIKE
// %termino%) pero en JS y sobre la copia local, para que funcione igual con
// o sin conexion.
export function search(term, { limit = 20 } = {}) {
    const needle = normalizeTerm(term);
    if (!needle) return [];

    const results = [];
    for (const product of cache) {
        const name = (product.name || '').toLowerCase();
        const barcode = (product.barcode || '').toLowerCase();
        const aliases = product.aliases || [];

        const matches = name.includes(needle)
            || barcode.includes(needle)
            || aliases.some((a) => (a || '').toLowerCase().includes(needle));

        if (matches) {
            results.push(product);
            if (results.length >= limit) break;
        }
    }

    return results;
}

// Descuenta stock del snapshot local tras una venta offline, para que el
// carrito y las busquedas siguientes reflejen lo que ya se vendio sin
// esperar al proximo refresh. baseQuantity va en la unidad base del
// producto (igual que en SaleService::processSale).
export async function applyLocalSaleDeduction(items) {
    const byProduct = new Map();
    for (const item of items) {
        const qty = (byProduct.get(item.product_id) || 0) + item.base_quantity;
        byProduct.set(item.product_id, qty);
    }

    const touched = [];
    byProduct.forEach((baseQuantity, productId) => {
        const product = getById(productId);
        if (!product) return;

        product.stock = Math.max(0, (product.stock || 0) - baseQuantity);
        product.total_stock = Math.max(0, (product.total_stock || 0) - baseQuantity);
        product.stock_status = product.stock <= 0 ? 'out_of_stock' : (product.stock <= 5 ? 'low_stock' : 'in_stock');
        touched.push(product);
    });

    for (const product of touched) {
        try {
            await db.put('products', product);
        } catch (e) {
            console.error('No se pudo persistir el stock local:', e);
        }
    }
}
