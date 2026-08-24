<?php

namespace App\Support;

/**
 * Recolorea toda la app sin tocar una sola clase de Tailwind.
 *
 * Tailwind v4 compila cada utilidad de color contra una variable CSS
 * (".bg-cyan-600{background-color:var(--color-cyan-600)}"), asi que
 * redefinir esas variables en :root repinta cualquier "cyan-*" que exista
 * en el codigo, sin volver a compilar ni tocar los ~300 usos repartidos en
 * decenas de vistas.
 *
 * Para que el resultado se vea bien (mismo contraste, mismos saltos claro/
 * oscuro) no se inventa una rampa nueva: se toma el tono (hue) del color que
 * elige el usuario y se aplica sobre la luminosidad/croma exactos que ya
 * usa la paleta "cyan" de Tailwind. Solo cambia el color, no la forma de la
 * escala.
 */
class ColorRamp
{
    /**
     * L (luminosidad, %) y C (croma) de cyan-50..950 en Tailwind v4, leidos
     * del CSS compilado. Son el molde de la escala; el hue se sustituye.
     *
     * @var array<int, array{0: float, 1: float}>
     */
    private const SHADES = [
        50 => [98.4, 0.019],
        100 => [95.6, 0.045],
        200 => [91.7, 0.080],
        300 => [86.5, 0.127],
        400 => [78.9, 0.154],
        500 => [71.5, 0.143],
        600 => [60.9, 0.126],
        700 => [52.0, 0.105],
        800 => [45.0, 0.085],
        900 => [39.8, 0.070],
        950 => [30.2, 0.056],
    ];

    /**
     * Declaraciones CSS que repintan la paleta "cyan" con el tono de $hex,
     * listas para meter dentro de un bloque `:root { ... }`. Null si el hex
     * no es valido (se conserva el cyan de fabrica).
     */
    public static function cssVariables(?string $hex): ?string
    {
        $hue = self::hueFromHex($hex);

        if ($hue === null) {
            return null;
        }

        $declarations = [];

        foreach (self::SHADES as $shade => [$lightness, $chroma]) {
            $declarations[] = "--color-cyan-{$shade}:oklch({$lightness}% {$chroma} {$hue})";
        }

        return implode(';', $declarations).';';
    }

    /**
     * Hue en grados [0, 360) del color, via conversion sRGB -> OKLab.
     * Se usan las matrices publicadas por Bjorn Ottosson (las mismas que
     * implementa CSS Color 4 / el propio Tailwind para su paleta oklch).
     */
    public static function hueFromHex(?string $hex): ?float
    {
        $rgb = self::parseHex($hex);

        if ($rgb === null) {
            return null;
        }

        [$r, $g, $b] = array_map(
            fn (int $channel) => self::srgbToLinear($channel / 255),
            $rgb
        );

        // Linear sRGB -> LMS (aproximacion cononica de OKLab)
        $l = 0.4122214708 * $r + 0.5363325363 * $g + 0.0514459929 * $b;
        $m = 0.2119034982 * $r + 0.6806995451 * $g + 0.1073969566 * $b;
        $s = 0.0883024619 * $r + 0.2817188376 * $g + 0.6299787005 * $b;

        $l = $l >= 0 ? $l ** (1 / 3) : -((-$l) ** (1 / 3));
        $m = $m >= 0 ? $m ** (1 / 3) : -((-$m) ** (1 / 3));
        $s = $s >= 0 ? $s ** (1 / 3) : -((-$s) ** (1 / 3));

        $a = 1.9779984951 * $l - 2.4285922050 * $m + 0.4505937099 * $s;
        $bLab = 0.0259040371 * $l + 0.7827717662 * $m - 0.8086757660 * $s;

        $hue = rad2deg(atan2($bLab, $a));

        return round($hue < 0 ? $hue + 360 : $hue, 1);
    }

    /**
     * @return array{0: int, 1: int, 2: int}|null
     */
    private static function parseHex(?string $hex): ?array
    {
        if ($hex === null) {
            return null;
        }

        $hex = ltrim(trim($hex), '#');

        if (preg_match('/^[0-9a-fA-F]{3}$/', $hex)) {
            $hex = implode('', array_map(fn ($c) => $c.$c, str_split($hex)));
        }

        if (! preg_match('/^[0-9a-fA-F]{6}$/', $hex)) {
            return null;
        }

        return [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        ];
    }

    private static function srgbToLinear(float $channel): float
    {
        return $channel <= 0.04045
            ? $channel / 12.92
            : (($channel + 0.055) / 1.055) ** 2.4;
    }
}
