# Módulo Offline (POS híbrido) - Plan de implementación

## Objetivo

Que el POS pueda seguir cobrando cuando se cae el internet (catálogo, precios
y stock ya descargados en el navegador), y que esas ventas se sincronicen
solas con el servidor al recuperar la conexión, sin duplicarlas y sin perder
ninguna.

## Contexto y decisiones tomadas

-   Catálogo: **400 productos** aprox. → se sincroniza **completo** en cada
    carga del POS (no hace falta delta por ahora, ~250 KB).
-   Prod corre en **HTTPS** (Cloudflare) → Service Worker viable.
-   Stock al sincronizar una venta offline: **se acepta y se marca**
    (`stock_issue`), nunca se rechaza. El stock puede quedar negativo; lo
    revisa un admin después.
-   Se permite cobrar offline **aunque el snapshot local diga stock 0**
    (con aviso visual). No se bloquea la venta por eso.
-   Una sola caja/sucursal activa por ahora → no hay que resolver
    concurrencia entre varias cajas offline a la vez.
-   Abrir y cerrar caja siguen requiriendo conexión.

## Arquitectura

Servidor sigue siendo la única fuente de verdad. El navegador guarda una
**copia de trabajo** (catálogo + ventas pendientes) en IndexedDB y la
reconcilia cuando hay red.

```
┌─────────────┐   catálogo completo    ┌──────────────┐
│   Servidor  │ ─────────────────────► │  IndexedDB   │
│  (MySQL)    │                        │  (navegador) │
│             │ ◄───────────────────── │              │
└─────────────┘   ventas de la outbox  └──────────────┘
```

-   `products` (store): snapshot de catálogo con precios/stock por sucursal,
    igual forma que hoy responde `PosController::searchProducts`.
-   `outbox` (store): ventas cobradas sin red, pendientes de enviar.
-   `meta` (store): versión de catálogo, última sincronización.

## Fases

### Fase 1 - Cimientos PWA

-   Service Worker manual (`public/sw.js`) que cachea el shell del POS y los
    assets de `public/build` (network-first con fallback a caché).
-   Quitar dependencias de CDN externo del layout (bootstrap-icons, fuente
    Inter) y auto-hospedarlas, porque sin red no cargan.
-   Indicador de conexión en el header del POS (heartbeat `GET /pos/ping`).
-   `navigator.storage.persist()` al entrar al POS.

### Fase 2 - Catálogo local y búsqueda offline

-   `GET /pos/catalog`: devuelve todos los productos activos de la sucursal
    con precios/stock ya resueltos (mismo shape que `searchProducts`).
-   IndexedDB (`resources/js/pos/db.js`, `catalog.js`) con store `products`
    indexado por `barcode`.
-   Búsqueda **local-first siempre** (con o sin red): mismo código busca en
    IndexedDB por nombre/barcode/alias, replicando `Product::scopeSearch`.
    El servidor deja de resolver búsquedas letra por letra.
-   Refresco de catálogo al entrar al POS y cada 5 min si hay red.

### Fase 3 - Outbox y sincronización

-   Migración: `sales.sold_at`, `sales.offline_ref`, `sales.stock_issue`.
-   `StoreSaleRequest` + `SaleService::processSale`: modo `offline` que
    omite la validación de stock y marca `stock_issue` en vez de rechazar.
-   Cliente: si el cobro falla por falta de red (o timeout), la venta se
    guarda en la outbox, se descuenta del stock local, se imprime el
    ticket con folio provisional `OFF-XXXXXX`.
-   Sincronización FIFO de la outbox al reconectar (evento `online` +
    heartbeat + cada 30s si hay pendientes), reusando el
    `idempotency_key` que ya existe para no duplicar ventas.
-   Contador de "ventas por sincronizar" visible; cerrar caja se bloquea
    mientras haya pendientes.

### Fase 4 - Endurecimiento (futuro, no en este alcance)

-   Gastos y movimientos de caja offline (mismo patrón de outbox).
-   Alertas de ventas con `stock_issue` sin revisar o pendientes viejas.
-   Ajustar `SESSION_LIFETIME` para turnos largos de caja.

## Riesgos aceptados

-   Si el navegador borra sus datos con ventas pendientes, esas ventas se
    pierden (no hay forma de evitarlo del todo; se mitiga con el contador
    visible y sync agresivo al reconectar).
-   Precios/stock offline pueden estar desactualizados frente a cambios
    hechos en admin durante el corte; la venta se registra al precio
    mostrado en pantalla (mismo comportamiento que ya tiene el POS online).
-   Sobreventa mientras la caja está offline es inherente al modelo;
    por eso el servidor acepta y marca en vez de rechazar.

## Detalles de implementación

-   Stock negativo: `Inventory::decrement()` no dispara el guard de
    `saving` que impide negativos en updates normales (ese guard solo
    protege escrituras via `save()`/`update()`), asi que una venta offline
    puede dejar el stock en negativo sin tocar esa validacion. Un producto
    sin registro de `Inventory` en la sucursal (caso raro: sucursal creada
    despues del producto) se crea en 0 antes de descontar.
-   El catalogo (`GET /pos/catalog`) reusa el mismo mapeo de producto que
    `searchProducts`, ahora extraido a `PosController::mapProductsForPos()`.
-   IndexedDB (`pos_offline`, stores `products`/`outbox`/`meta`) vive en
    `resources/js/pos/{db,catalog,outbox,sync,connection}.js`, expuesto
    como `window.PosOffline` desde `resources/js/app.js`. El script inline
    de `pos/index.blade.php` lo consume porque los modulos de Vite
    (`@vite`, deferred) siempre terminan de ejecutarse antes de que dispare
    `DOMContentLoaded`.
-   bootstrap-icons se saco del CDN externo y se instalo como dependencia
    (`resources/css/app.css`), porque sin red no cargaba.

## Estado de implementación

-   [x] Fase 1 - Cimientos PWA
-   [x] Fase 2 - Catálogo local y búsqueda offline
-   [x] Fase 3 - Outbox y sincronización
-   [ ] Fase 4 - Endurecimiento (gastos/movimientos offline, alertas, sesion larga)
