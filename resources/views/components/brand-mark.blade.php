{{--
    Logo configurado, o el icono de tienda por defecto si no hay uno.
    Reusado en el sidebar y en el login para que ambos cambien juntos.

    $boxClass   clases del contenedor cuadrado (tamano + radio), ej. "w-9 h-9 rounded-lg"
    $iconClass  clases del icono de respaldo, ej. "text-base"
--}}
@props(['boxClass' => 'w-9 h-9 rounded-lg', 'iconClass' => ''])
@if(logo_url())
<img src="{{ logo_url() }}" alt="{{ setting('site_name') }}" class="{{ $boxClass }} object-cover flex-shrink-0">
@else
<div class="{{ $boxClass }} bg-cyan-600 flex items-center justify-center flex-shrink-0">
    <i class="bi bi-shop text-white {{ $iconClass }}"></i>
</div>
@endif
