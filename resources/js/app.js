import './bootstrap';
import * as connection from './pos/connection.js';
import * as catalog from './pos/catalog.js';
import * as outbox from './pos/outbox.js';
import * as sync from './pos/sync.js';

// Expuesto para que el script inline de resources/views/pos/index.blade.php
// lo use: ese script corre en el DOMContentLoaded, que siempre dispara
// despues de que este modulo (cargado via @vite, type=module=deferred) ya
// se ejecuto, asi que window.PosOffline ya existe cuando se necesita.
window.PosOffline = { connection, catalog, outbox, sync };

// El Service Worker y el heartbeat solo tienen sentido dentro del POS: es
// la unica pantalla que necesita seguir funcionando sin servidor.
if (location.pathname.startsWith('/pos')) {
    if ('serviceWorker' in navigator) {
        // Requiere HTTPS (o localhost); en el resto de entornos el registro
        // simplemente falla y el POS sigue funcionando online-only.
        navigator.serviceWorker.register('/sw.js').catch((e) => {
            console.error('No se pudo registrar el Service Worker:', e);
        });
    }

    if (navigator.storage && navigator.storage.persist) {
        // Best-effort: evita que el navegador borre IndexedDB (catalogo y
        // ventas pendientes) por presion de espacio.
        navigator.storage.persist().catch(() => {});
    }

    connection.start();
    sync.start();
}

// Imprime un ticket en un iframe aislado, con su propia hoja de estilos y sin
// el layout de la app (sidebar, header, etc). Imprimir directo sobre la
// pagina (window.print + @media print) hace que el alto de pagina "auto" de
// las impresoras termicas tome el alto de TODO el documento oculto, no solo
// el del ticket, y salga un recibo carisimo de largo.
window.printTicket = function (bodyHtml) {
    let iframe = document.getElementById('ticket-print-frame');
    if (!iframe) {
        iframe = document.createElement('iframe');
        iframe.id = 'ticket-print-frame';
        iframe.setAttribute('aria-hidden', 'true');
        iframe.style.cssText = 'position:fixed; width:0; height:0; border:0; visibility:hidden;';
        document.body.appendChild(iframe);
    }

    const stylesheets = Array.from(document.querySelectorAll('link[rel="stylesheet"]'))
        .map((link) => `<link rel="stylesheet" href="${link.href}">`)
        .join('');

    const doc = iframe.contentDocument;
    doc.open();
    doc.write(`<!doctype html>
<html>
<head>
<meta charset="utf-8">
${stylesheets}
<style>@page { size: 80mm auto; margin: 0; } html, body { margin: 0; padding: 0; }</style>
</head>
<body class="font-mono text-xs leading-relaxed w-[80mm] mx-auto p-2">${bodyHtml}</body>
</html>`);
    doc.close();

    const triggerPrint = () => {
        iframe.contentWindow.focus();
        iframe.contentWindow.print();
    };

    // Pequeño margen para que las hojas de estilo externas terminen de
    // aplicarse antes de imprimir (el load del iframe no lo garantiza).
    setTimeout(triggerPrint, 250);
};
