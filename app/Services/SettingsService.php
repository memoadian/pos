<?php

namespace App\Services;

use App\Models\Setting;
use App\Support\ColorRamp;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

/**
 * Ajustes del sitio tipo WordPress: pares clave/valor en la tabla `settings`,
 * leidos una sola vez y cacheados de forma indefinida. Cada guardado invalida
 * la cache, asi que el costo por request es cero salvo el primero despues de
 * un cambio.
 */
class SettingsService
{
    private const CACHE_KEY = 'settings.all';

    /**
     * Valor de fabrica para cada ajuste conocido, para que la pantalla de
     * configuracion (y el resto de la app) funcionen aun sin una sola fila
     * guardada todavia.
     *
     * @var array<string, string|null>
     */
    private const DEFAULTS = [
        'site_name' => 'POS Limpieza',
        'primary_color' => null,
        'logo_path' => null,
        'business_name' => null,
        'business_address' => null,
        'business_phone' => null,
        'business_tax_id' => null,
        'ticket_footer' => '¡Gracias por su compra!',
        'currency_symbol' => '$',
    ];

    /**
     * Lo que hay guardado en la tabla, sin mezclar con los valores de
     * fabrica: una fila con valor vacio (el admin borro el campo) debe poder
     * distinguirse de una fila que nunca se guardo, para que get() sepa
     * cuando caer al default sin perderlo permanentemente.
     *
     * @return array<string, string|null>
     */
    private function stored(): array
    {
        // Setting::all() (no el query builder) para que pase por el accessor
        // getValueAttribute() del modelo: hoy todos los ajustes son texto plano,
        // pero un booleano/entero futuro ya se castea solo sin tocar este servicio.
        return Cache::rememberForever(
            self::CACHE_KEY,
            fn () => Setting::all()->pluck('value', 'key')->all(),
        );
    }

    /**
     * Valores efectivos de todos los ajustes conocidos: lo guardado donde
     * exista y no este vacio, el default de fabrica en cualquier otro caso.
     * Util para precargar el formulario de configuracion.
     *
     * @return array<string, string|null>
     */
    public function all(): array
    {
        $stored = $this->stored();

        $effective = [];
        foreach (self::DEFAULTS as $key => $default) {
            $effective[$key] = $this->resolve($stored[$key] ?? null, $default);
        }

        return $effective;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $stored = $this->stored()[$key] ?? null;

        // Tres niveles: lo guardado, el default de fabrica de la clave, y
        // solo si la clave ni siquiera es conocida, el default del llamador.
        return $this->resolve($stored, self::DEFAULTS[$key] ?? $default);
    }

    private function resolve(mixed $stored, mixed $default): mixed
    {
        return $stored === null || $stored === '' ? $default : $stored;
    }

    /**
     * Guarda varios ajustes de un jalon e invalida la cache una sola vez,
     * en vez de una por cada `set()` individual.
     *
     * @param  array<string, string|null>  $values
     */
    public function setMany(array $values): void
    {
        foreach ($values as $key => $value) {
            Setting::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        }

        Cache::forget(self::CACHE_KEY);
    }

    public function set(string $key, ?string $value): void
    {
        $this->setMany([$key => $value]);
    }

    /**
     * URL publica del logo, o null si no hay uno configurado (las vistas
     * caen entonces al icono de marca por defecto).
     */
    public function logoUrl(): ?string
    {
        $path = $this->get('logo_path');

        return $path ? Storage::disk('public')->url($path) : null;
    }

    /**
     * Declaraciones CSS que repintan la paleta "cyan" con el color primario
     * configurado, listas para un bloque `:root { ... }`. Null si no hay
     * color configurado (se deja el cyan de fabrica de Tailwind).
     */
    public function primaryColorCss(): ?string
    {
        return ColorRamp::cssVariables($this->get('primary_color'));
    }
}
