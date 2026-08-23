{{--
    Barra de resultados de una tabla paginada.

    $paginator  LengthAwarePaginator ya filtrado
    $total      Total absoluto, sin filtros (para distinguir "no hay" de "no coincide")
    $singular   Nombre de la entidad en singular ("producto")
    $plural     Nombre de la entidad en plural ("productos")
    $icon       Clase de Bootstrap Icons ("bi-box-seam")
--}}
@php
    $matching = $paginator->total();
    $filtered = $matching !== $total;
    // Que tanto de la lista queda arriba de lo que se esta viendo: da sensacion
    // de posicion cuando hay muchas paginas.
    $progress = $matching > 0 ? round($paginator->lastItem() / $matching * 100) : 0;
@endphp
<div class="relative flex flex-wrap items-center justify-between gap-x-4 gap-y-2 px-4 py-2.5 bg-gradient-to-r from-slate-50 to-white border-b border-slate-200">
    @if($matching === 0)
        <div class="flex items-center gap-2.5">
            <span class="w-7 h-7 rounded-lg bg-white border border-slate-200 flex items-center justify-center shrink-0">
                <i class="bi {{ $filtered ? 'bi-search' : $icon }} text-slate-400 text-sm"></i>
            </span>
            <p class="text-sm text-slate-500">
                {{ $filtered ? "Ningún {$singular} coincide con los filtros" : "Todavía no hay {$plural} registrados" }}
            </p>
        </div>

        {{-- Con la tabla vacia por un filtro, salir del filtro es la unica accion util. --}}
        @if($filtered)
        <a href="{{ request()->url() }}"
           class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-medium text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition-colors">
            <i class="bi bi-x-circle"></i>Limpiar filtros
        </a>
        @endif
    @else
        <div class="flex items-center gap-2.5 min-w-0">
            <span class="w-7 h-7 rounded-lg bg-white border border-slate-200 flex items-center justify-center shrink-0">
                <i class="bi {{ $icon }} text-cyan-600 text-sm"></i>
            </span>
            <p class="text-sm text-slate-600 truncate">
                <span class="inline-flex items-center px-1.5 py-0.5 rounded-md bg-white border border-slate-200 text-xs font-semibold text-slate-900 tabular-nums align-middle">{{ number_format($paginator->firstItem()) }}–{{ number_format($paginator->lastItem()) }}</span>
                <span class="mx-0.5">de</span>
                <span class="font-semibold text-slate-900 tabular-nums">{{ number_format($matching) }}</span>
                {{ $matching === 1 ? $singular : $plural }}
            </p>
        </div>

        <div class="flex items-center gap-2">
            {{-- Con filtros activos el numero de arriba ya no es el del catalogo: se aclara. --}}
            @if($filtered)
            <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded-full bg-cyan-50 border border-cyan-200 text-xs font-medium text-cyan-700 tabular-nums">
                <i class="bi bi-funnel-fill"></i>filtrado de {{ number_format($total) }}
            </span>
            @endif

            @if($paginator->hasPages())
            <span class="text-xs text-slate-500 tabular-nums whitespace-nowrap">
                Página {{ number_format($paginator->currentPage()) }} de {{ number_format($paginator->lastPage()) }}
            </span>
            @endif
        </div>

        @if($paginator->hasPages())
        <div class="absolute bottom-0 left-0 h-0.5 bg-cyan-500/70 transition-all" style="width: {{ $progress }}%"></div>
        @endif
    @endif
</div>
