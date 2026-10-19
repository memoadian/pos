@extends('layouts.app')
@section('title', 'Importar Inventario')
@section('content')
@php
    $importErrors = session('importErrors', []);
    $importErrorRows = session('importErrorRows', count($importErrors));
    $importErrorSummary = session('importErrorSummary', []);
    arsort($importErrorSummary);
@endphp
<div class="max-w-3xl mx-auto space-y-6">
    @include('components.alerts')
    <div class="flex items-center gap-3">
        <a href="{{ route('inventory.index') }}" class="p-2 hover:bg-slate-100 rounded-lg transition-colors"><i class="bi bi-arrow-left text-slate-600"></i></a>
        <div><h1 class="text-xl font-semibold text-slate-900">Importar Inventario</h1><p class="text-sm text-slate-500 mt-1">Actualiza el stock de {{ $branch->name }} desde un Excel o CSV</p></div>
    </div>

    @if($importErrorRows > 0)
    <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 flex items-center gap-3">
        <i class="bi bi-exclamation-triangle-fill text-amber-600 text-lg flex-shrink-0"></i>
        <p class="text-sm text-amber-900 flex-1">
            <strong>{{ $importErrorRows }}</strong> fila(s) del último archivo se omitieron por errores.
        </p>
        <button type="button" onclick="ImportErrors.open()" class="px-3 py-1.5 bg-amber-600 hover:bg-amber-700 text-white text-sm font-medium rounded-lg transition-colors">
            Ver errores
        </button>
    </div>
    @endif

    <div class="bg-white rounded-lg border border-slate-200 p-6 space-y-4">
        <h2 class="text-sm font-medium text-slate-900">Cómo funciona</h2>
        <ul class="text-sm text-slate-600 space-y-1.5 list-disc pl-5">
            <li>Descarga el inventario actual de <strong>{{ $branch->name }}</strong>.</li>
            <li>Edita únicamente la columna <strong>Stock Actual</strong>. Las demás columnas son informativas.</li>
            <li>No cambies el <strong>Código Barras</strong> ni la <strong>Sucursal</strong>: el código identifica el producto y la sucursal evita aplicar el archivo en el lugar equivocado.</li>
            <li>Los productos que no existan en el catálogo se omiten y se reportan como error.</li>
            <li>Cada cambio de stock queda registrado en Movimientos como un ajuste, con el stock anterior.</li>
            <li>Volver a importar el mismo archivo no duplica nada: las filas que ya coinciden se dejan intactas.</li>
        </ul>

        <div class="flex flex-wrap gap-1.5 pt-2 border-t border-slate-100">
            @foreach(['Código Barras', 'Producto', 'Departamento', 'Sucursal', 'Unidad', 'Stock Actual'] as $column)
            <span class="px-2 py-0.5 text-xs rounded {{ $column === 'Stock Actual' ? 'bg-cyan-100 text-cyan-800 font-medium' : 'bg-slate-100 text-slate-600' }}">{{ $column }}</span>
            @endforeach
        </div>
    </div>

    <div class="bg-cyan-50 border border-cyan-200 rounded-lg p-4 flex flex-col sm:flex-row sm:items-center gap-4">
        <div class="w-10 h-10 rounded-full bg-cyan-100 flex items-center justify-center flex-shrink-0">
            <i class="bi bi-file-earmark-spreadsheet text-cyan-700 text-lg"></i>
        </div>
        <div class="flex-1">
            <h2 class="text-sm font-semibold text-cyan-900">Descarga el punto de partida</h2>
            <p class="text-sm text-cyan-800 mt-0.5">Incluye el inventario de la sucursal activa con los filtros que tengas aplicados en la pantalla.</p>
        </div>
        <a href="{{ route('inventory.export') }}" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-cyan-600 hover:bg-cyan-700 text-white text-sm font-semibold rounded-lg transition-colors whitespace-nowrap">
            <i class="bi bi-download"></i><span>Descargar inventario</span>
        </a>
    </div>

    <form method="POST" action="{{ route('inventory.import.store') }}" enctype="multipart/form-data" class="bg-white rounded-lg border border-slate-200">
        @csrf
        <div class="p-6 space-y-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Archivo <span class="text-red-500">*</span></label>
                <input type="file" name="file" accept=".xlsx,.xls,.csv" required class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none transition @error('file') border-red-500 @enderror">
                @error('file')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                <p class="mt-1 text-xs text-slate-500">Formatos aceptados: .xlsx, .xls, .csv (máx. 10MB)</p>
            </div>
        </div>
        <div class="flex items-center gap-3 px-6 py-4 bg-slate-50 border-t border-slate-200 rounded-b-lg">
            <button type="submit" class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-cyan-600 hover:bg-cyan-700 text-white text-sm font-medium rounded-lg transition-colors"><i class="bi bi-upload"></i><span>Importar inventario</span></button>
            <a href="{{ route('inventory.index') }}" class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-medium rounded-lg transition-colors"><span>Cancelar</span></a>
        </div>
    </form>
</div>

@if($importErrorRows > 0)
<div id="importErrorsModal" class="fixed inset-0 z-[90] hidden">
    <div class="absolute inset-0 bg-black/50" onclick="ImportErrors.close()"></div>
    <div class="relative flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[85vh] flex flex-col">
            <div class="flex items-start gap-4 p-6 border-b border-slate-200">
                <div class="w-10 h-10 rounded-full bg-amber-100 flex items-center justify-center flex-shrink-0">
                    <i class="bi bi-exclamation-triangle text-amber-600 text-lg"></i>
                </div>
                <div class="min-w-0 flex-1">
                    <h3 class="text-base font-semibold text-slate-900">{{ $importErrorRows }} fila(s) omitidas</h3>
                    <p class="mt-1 text-sm text-slate-500">Corrige estas filas en tu archivo y vuelve a importarlo. Las filas válidas ya se guardaron.</p>
                </div>
                <button type="button" onclick="ImportErrors.close()" class="p-1 -m-1 text-slate-400 hover:text-slate-600"><i class="bi bi-x-lg"></i></button>
            </div>

            <div class="overflow-y-auto p-6 space-y-6">
                @if(! empty($importErrorSummary))
                <div>
                    <p class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-2">Resumen por tipo de error</p>
                    <ul class="divide-y divide-slate-100 border border-slate-200 rounded-lg">
                        @foreach($importErrorSummary as $reason => $count)
                        <li class="flex items-start gap-3 px-3 py-2">
                            <span class="px-2 py-0.5 bg-amber-100 text-amber-800 text-xs font-semibold rounded flex-shrink-0">{{ $count }}</span>
                            <span class="text-sm text-slate-700">{{ $reason }}</span>
                        </li>
                        @endforeach
                    </ul>
                </div>
                @endif

                <div>
                    <p class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-2">
                        Detalle por fila
                        @if(count($importErrors) < $importErrorRows)
                        <span class="normal-case font-normal">(las primeras {{ count($importErrors) }} de {{ $importErrorRows }})</span>
                        @endif
                    </p>
                    <ul class="divide-y divide-slate-100 border border-slate-200 rounded-lg">
                        @foreach($importErrors as $rowError)
                        <li class="px-3 py-2">
                            <p class="text-sm font-medium text-slate-900">Fila {{ $rowError['row'] }}</p>
                            <ul class="mt-0.5 space-y-0.5">
                                @foreach($rowError['errors'] as $error)
                                <li class="text-sm text-slate-600">· {{ $error }}</li>
                                @endforeach
                            </ul>
                        </li>
                        @endforeach
                    </ul>
                </div>
            </div>

            <div class="flex items-center gap-3 px-6 py-4 bg-slate-50 border-t border-slate-200 rounded-b-lg">
                <button type="button" onclick="ImportErrors.downloadCsv()" class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-medium rounded-lg transition-colors">
                    <i class="bi bi-download"></i><span>Descargar errores (CSV)</span>
                </button>
                <button type="button" onclick="ImportErrors.close()" class="flex-1 px-4 py-2.5 bg-cyan-600 hover:bg-cyan-700 text-white text-sm font-medium rounded-lg transition-colors">Entendido</button>
            </div>
        </div>
    </div>
</div>
<script>
const ImportErrors = (function () {
    const modal = document.getElementById('importErrorsModal');
    const rows = @json($importErrors);

    function close() { modal.classList.add('hidden'); }
    function open() { modal.classList.remove('hidden'); }

    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') close(); });

    return {
        open,
        close,
        downloadCsv() {
            const escape = (value) => '"' + String(value).replace(/"/g, '""') + '"';
            const lines = [['Fila', 'Error'].join(',')];

            rows.forEach((row) => {
                row.errors.forEach((error) => lines.push([row.row, escape(error)].join(',')));
            });

            const blob = new Blob(['﻿' + lines.join('\n')], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = 'errores-inventario.csv';
            link.click();
            URL.revokeObjectURL(link.href);
        },
    };
})();

ImportErrors.open();
</script>
@endif
@endsection
