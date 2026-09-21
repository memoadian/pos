// Deteccion de conectividad real. navigator.onLine solo dice si hay una
// interfaz de red activa (wifi conectado pero sin internet cuenta como
// "online"), asi que ademas de los eventos del navegador se hace un
// heartbeat contra el servidor. El heartbeat es la fuente de verdad; los
// eventos online/offline solo adelantan el siguiente intento.

const PING_URL = '/ping';
const PING_INTERVAL_MS = 15000;
const PING_TIMEOUT_MS = 5000;

let online = navigator.onLine;
const listeners = new Set();

function setOnline(value) {
    if (value === online) return;
    online = value;
    listeners.forEach((fn) => {
        try {
            fn(online);
        } catch (e) {
            console.error('Error en listener de conexion:', e);
        }
    });
}

async function ping() {
    const controller = new AbortController();
    const timeout = setTimeout(() => controller.abort(), PING_TIMEOUT_MS);

    try {
        const response = await fetch(PING_URL, {
            method: 'GET',
            cache: 'no-store',
            credentials: 'same-origin',
            signal: controller.signal,
        });
        setOnline(response.ok);
    } catch (e) {
        setOnline(false);
    } finally {
        clearTimeout(timeout);
    }
}

export function isOnline() {
    return online;
}

export function onChange(fn) {
    listeners.add(fn);
    return () => listeners.delete(fn);
}

export function checkNow() {
    return ping();
}

export function start() {
    window.addEventListener('online', ping);
    window.addEventListener('offline', () => setOnline(false));
    // Revisa tambien cuando la pestaña vuelve a estar visible (el usuario
    // regreso de otra app y quiza ya hay red de nuevo).
    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') ping();
    });
    ping();
    setInterval(ping, PING_INTERVAL_MS);
}
