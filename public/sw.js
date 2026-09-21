// Service Worker del POS. Objetivo unico: que la pantalla /pos y sus
// assets (CSS/JS de Vite) sigan cargando si el navegador se recarga sin
// internet. No cachea nada de datos (catalogo, ventas, etc): eso lo maneja
// IndexedDB desde resources/js/pos/*, este archivo solo resuelve el
// "shell" de la pagina.
//
// Sube la version del cache cuando cambie esta logica (no hace falta
// tocarlo por cada build de Vite: los assets de /build/ tienen hash en el
// nombre y se cachean en runtime, ver abajo).
const CACHE_VERSION = 'pos-shell-v1';
const SHELL_URL = '/pos';

self.addEventListener('install', (event) => {
    self.skipWaiting();
    event.waitUntil(
        caches.open(CACHE_VERSION).then((cache) => {
            // Best-effort: si esto falla (por ejemplo, se instala el SW sin
            // sesion todavia) no debe tumbar la instalacion.
            return cache.add(SHELL_URL).catch(() => {});
        })
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) => Promise.all(
            keys.filter((key) => key !== CACHE_VERSION).map((key) => caches.delete(key))
        )).then(() => self.clients.claim())
    );
});

function isBuildAsset(url) {
    return url.origin === self.location.origin && url.pathname.startsWith('/build/');
}

function isShellRequest(request, url) {
    return request.mode === 'navigate' && url.pathname === SHELL_URL;
}

self.addEventListener('fetch', (event) => {
    const { request } = event;
    if (request.method !== 'GET') return; // POST (checkout, etc.) nunca se intercepta

    const url = new URL(request.url);
    if (url.origin !== self.location.origin) return; // fuentes/CDNs externos: sin tocar

    if (isBuildAsset(url)) {
        // Cache-first: el nombre del archivo trae hash de contenido, un
        // asset nunca cambia de contenido bajo el mismo nombre.
        event.respondWith(
            caches.open(CACHE_VERSION).then(async (cache) => {
                const cached = await cache.match(request);
                if (cached) return cached;

                const response = await fetch(request);
                if (response.ok) cache.put(request, response.clone());
                return response;
            })
        );
        return;
    }

    if (isShellRequest(request, url)) {
        // Network-first: siempre se prefiere la version fresca (permisos,
        // datos del layout, etc.); si no hay red, se sirve la ultima que
        // funciono.
        event.respondWith(
            fetch(request)
                .then((response) => {
                    if (response.ok) {
                        caches.open(CACHE_VERSION).then((cache) => cache.put(request, response.clone()));
                    }
                    return response;
                })
                .catch(() => caches.match(request))
        );
        return;
    }

    // Cualquier otra cosa (catalogo, checkout, ping, otras paginas del
    // sistema) pasa de largo: sin cache, para que la app JS vea el error
    // de red real y decida (encolar en outbox, usar el snapshot local, etc).
});
