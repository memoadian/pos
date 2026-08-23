{{--
    Chip con el total absoluto de una entidad, para poner junto al título.

    $count  Total sin filtros
    $icon   Clase de Bootstrap Icons ("bi-box-seam")
    $label  Texto del tooltip ("productos en el catálogo")
--}}
<span class="inline-flex items-center gap-1.5 pl-2 pr-2.5 py-1 rounded-full bg-white border border-slate-200 shadow-sm"
      title="{{ number_format($count) }} {{ $label }}">
    <i class="bi {{ $icon }} text-cyan-600 text-xs"></i>
    <span class="text-sm font-semibold text-slate-900 tabular-nums leading-none">{{ number_format($count) }}</span>
</span>
