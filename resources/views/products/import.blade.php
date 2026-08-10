@extends('layouts.app')
@section('title', 'Importar Productos')
@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    @include('components.alerts')
    <div class="flex items-center gap-3">
        <a href="{{ route('products.index') }}" class="p-2 hover:bg-slate-100 rounded-lg transition-colors"><i class="bi bi-arrow-left text-slate-600"></i></a>
        <div><h1 class="text-xl font-semibold text-slate-900">Importar Productos</h1><p class="text-sm text-slate-500 mt-1">Carga un archivo Excel o CSV para crear o actualizar productos en bulk</p></div>
    </div>

    <div class="bg-white rounded-lg border border-slate-200 p-6 space-y-4">
        <h2 class="text-sm font-medium text-slate-900">Instrucciones</h2>
        <ul class="text-sm text-slate-600 space-y-1.5 list-disc pl-5">
            <li>Descarga la plantilla y llena una fila por producto.</li>
            <li><strong>Departamento</strong> y <strong>Tipo Venta</strong> deben coincidir con un nombre ya existente en el sistema.</li>
            <li><strong>Código Barras</strong> es obligatorio: si ya existe, ese producto se actualiza; si no existe, se crea uno nuevo.</li>
            <li>Las columnas de <strong>Super Mayoreo</strong> son opcionales: déjalas vacías si ese producto no maneja ese nivel de precio.</li>
            <li><strong>Activo</strong> es opcional (Si/No); si se deja vacío el producto se crea activo.</li>
        </ul>
        <a href="{{ route('products.import.template') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-medium rounded-lg transition-colors">
            <i class="bi bi-download"></i><span>Descargar plantilla</span>
        </a>
    </div>

    <form method="POST" action="{{ route('products.import.store') }}" enctype="multipart/form-data" class="bg-white rounded-lg border border-slate-200">
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
            <button type="submit" class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-cyan-600 hover:bg-cyan-700 text-white text-sm font-medium rounded-lg transition-colors"><i class="bi bi-upload"></i><span>Importar</span></button>
            <a href="{{ route('products.index') }}" class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-medium rounded-lg transition-colors"><span>Cancelar</span></a>
        </div>
    </form>

    @if(session('importErrors') && count(session('importErrors')) > 0)
    <div class="bg-amber-50 border border-amber-200 rounded-lg p-4">
        <div class="flex items-center gap-3 mb-3">
            <i class="bi bi-exclamation-triangle-fill text-amber-600 text-lg flex-shrink-0"></i>
            <p class="text-sm font-medium text-amber-900">Filas omitidas por errores</p>
        </div>
        <ul class="space-y-2">
            @foreach(session('importErrors') as $rowError)
            <li class="text-sm text-amber-800"><strong>Fila {{ $rowError['row'] }}:</strong> {{ implode(', ', $rowError['errors']) }}</li>
            @endforeach
        </ul>
    </div>
    @endif
</div>
@endsection
