import './bootstrap';

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
