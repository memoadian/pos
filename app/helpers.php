<?php

use App\Services\SettingsService;

if (! function_exists('setting')) {
    /**
     * Lee un ajuste del sitio (nombre, color, datos del ticket...). Cacheado
     * de forma indefinida por SettingsService: no cuesta una consulta por
     * llamada.
     */
    function setting(string $key, mixed $default = null): mixed
    {
        return app(SettingsService::class)->get($key, $default);
    }
}

if (! function_exists('money')) {
    /**
     * Formatea un monto con el simbolo de moneda configurado, en vez del
     * "$" fijo que antes estaba escrito a mano en cada vista.
     */
    function money(float|int|string $amount, int $decimals = 2): string
    {
        return setting('currency_symbol', '$').number_format((float) $amount, $decimals);
    }
}

if (! function_exists('logo_url')) {
    /**
     * URL publica del logo configurado, o null si no hay uno (la vista cae
     * entonces al icono de marca por defecto).
     */
    function logo_url(): ?string
    {
        return app(SettingsService::class)->logoUrl();
    }
}
