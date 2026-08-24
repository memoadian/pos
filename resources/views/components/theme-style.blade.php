{{--
    Repinta la paleta "cyan" de Tailwind con el color primario configurado.

    Tailwind v4 compila cada utilidad de color contra una variable CSS
    (".bg-cyan-600{background-color:var(--color-cyan-600)}"), asi que
    redefinir esas variables aqui repinta cualquier "cyan-*" que exista en
    el codigo (botones, focus rings, badges...) sin recompilar ni tocar una
    sola clase. Ver App\Support\ColorRamp.

    Sin color configurado no se emite nada: se queda el cyan de fabrica.
--}}
@php($themeCss = app(\App\Services\SettingsService::class)->primaryColorCss())
@if($themeCss)
<style>:root{ {!! $themeCss !!} }</style>
@endif
