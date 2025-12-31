@extends('layouts.app')

@section('title', 'Editar Departamento')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">
    <!-- Alerts -->
    @include('components.alerts')

    <!-- Header -->
    <div class="flex items-center gap-3">
        <a href="{{ route('departments.index') }}"
           class="p-2 hover:bg-slate-100 rounded-lg transition-colors">
            <i class="bi bi-arrow-left text-slate-600"></i>
        </a>
        <div>
            <h1 class="text-xl font-semibold text-slate-900">Editar Departamento</h1>
            <p class="text-sm text-slate-500 mt-1">Actualiza la información del departamento</p>
        </div>
    </div>

    <!-- Form -->
    <form method="POST" action="{{ route('departments.update', $department) }}" class="bg-white rounded-lg border border-slate-200">
        @csrf
        @method('PUT')

        <div class="p-6 space-y-5">
            <!-- Nombre -->
            <div>
                <label for="name" class="block text-sm font-medium text-slate-700 mb-2">
                    Nombre del Departamento <span class="text-red-500">*</span>
                </label>
                <input type="text"
                       id="name"
                       name="name"
                       value="{{ old('name', $department->name) }}"
                       required
                       autofocus
                       class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none transition @error('name') border-red-500 @enderror"
                       placeholder="Ej: Jarcería">
                @error('name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Info de productos -->
            @if($department->products()->count() > 0)
            <div class="p-4 bg-blue-50 border border-blue-200 rounded-lg">
                <div class="flex items-start gap-3">
                    <i class="bi bi-info-circle text-blue-600 text-lg"></i>
                    <div class="flex-1">
                        <p class="text-sm font-medium text-blue-900">Productos asociados</p>
                        <p class="text-sm text-blue-700 mt-1">
                            Este departamento tiene {{ $department->products()->count() }} producto(s) asociado(s).
                        </p>
                    </div>
                </div>
            </div>
            @endif
        </div>

        <div class="flex items-center gap-3 px-6 py-4 bg-slate-50 border-t border-slate-200 rounded-b-lg">
            <button type="submit"
                    class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-cyan-600 hover:bg-cyan-700 text-white text-sm font-medium rounded-lg transition-colors">
                <i class="bi bi-check-lg"></i>
                <span>Actualizar Departamento</span>
            </button>
            <a href="{{ route('departments.index') }}"
               class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-medium rounded-lg transition-colors">
                <span>Cancelar</span>
            </a>
        </div>
    </form>
</div>
@endsection
