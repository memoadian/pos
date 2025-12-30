@extends('layouts.app')

@section('title', 'Nuevo Permiso')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">
    <!-- Alerts -->
    @include('components.alerts')

    <!-- Header -->
    <div class="flex items-center gap-3">
        <a href="{{ route('permissions.index') }}"
           class="p-2 hover:bg-slate-100 rounded-lg transition-colors">
            <i class="bi bi-arrow-left text-slate-600"></i>
        </a>
        <div>
            <h1 class="text-xl font-semibold text-slate-900">Nuevo Permiso</h1>
            <p class="text-sm text-slate-500 mt-1">Crea un nuevo permiso en el sistema</p>
        </div>
    </div>

    <!-- Formulario -->
    <form method="POST"
          action="{{ route('permissions.store') }}"
          class="bg-white rounded-lg border border-slate-200 p-6 space-y-5">
        @csrf

        <!-- Nombre -->
        <div>
            <label for="name" class="block text-sm font-medium text-slate-700 mb-1.5">
                Nombre del Permiso <span class="text-red-500">*</span>
            </label>
            <input type="text"
                   id="name"
                   name="name"
                   value="{{ old('name') }}"
                   class="w-full px-4 py-2.5 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none transition @error('name') border-red-400 @enderror"
                   placeholder="Ej: editar productos"
                   required>
            <p class="mt-1 text-xs text-slate-500">Usa minúsculas y espacios. Ej: "crear ventas", "ver reportes"</p>
            @error('name')
                <p class="mt-1.5 text-sm text-red-600 flex items-center gap-1">
                    <i class="bi bi-exclamation-circle"></i>
                    {{ $message }}
                </p>
            @enderror
        </div>

        <!-- Descripción -->
        <div>
            <label for="description" class="block text-sm font-medium text-slate-700 mb-1.5">
                Descripción
            </label>
            <textarea id="description"
                      name="description"
                      rows="2"
                      class="w-full px-4 py-2.5 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none transition @error('description') border-red-400 @enderror"
                      placeholder="Describe qué permite hacer este permiso...">{{ old('description') }}</textarea>
            @error('description')
                <p class="mt-1.5 text-sm text-red-600 flex items-center gap-1">
                    <i class="bi bi-exclamation-circle"></i>
                    {{ $message }}
                </p>
            @enderror
        </div>

        <!-- Grupo -->
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1.5">
                Grupo <span class="text-red-500">*</span>
            </label>
            <div class="flex gap-2">
                <select id="groupSelect"
                        class="flex-1 px-4 py-2.5 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none transition"
                        onchange="handleGroupChange()">
                    <option value="">Seleccionar grupo existente...</option>
                    @foreach($groups as $group)
                    <option value="{{ $group }}" {{ old('group') === $group ? 'selected' : '' }}>
                        {{ $group }}
                    </option>
                    @endforeach
                    <option value="__new__">+ Crear nuevo grupo</option>
                </select>
                <input type="text"
                       id="group"
                       name="group"
                       value="{{ old('group') }}"
                       class="flex-1 px-4 py-2.5 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none transition @error('group') border-red-400 @enderror {{ old('group') ? '' : 'hidden' }}"
                       placeholder="Nombre del nuevo grupo">
            </div>
            @error('group')
                <p class="mt-1.5 text-sm text-red-600 flex items-center gap-1">
                    <i class="bi bi-exclamation-circle"></i>
                    {{ $message }}
                </p>
            @enderror
        </div>

        <!-- Acciones -->
        <div class="flex items-center gap-3 pt-4 border-t border-slate-200">
            <button type="submit"
                    class="flex-1 px-4 py-2.5 bg-cyan-600 hover:bg-cyan-700 text-white text-sm font-medium rounded-lg transition-colors">
                <i class="bi bi-check-lg mr-2"></i>
                Crear Permiso
            </button>
            <a href="{{ route('permissions.index') }}"
               class="flex-1 px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-medium rounded-lg transition-colors text-center">
                Cancelar
            </a>
        </div>
    </form>
</div>
@endsection

@section('scripts')
<script>
function handleGroupChange() {
    const select = document.getElementById('groupSelect');
    const input = document.getElementById('group');

    if (select.value === '__new__') {
        input.classList.remove('hidden');
        input.value = '';
        input.focus();
    } else {
        input.value = select.value;
        if (select.value === '') {
            input.classList.remove('hidden');
        } else {
            input.classList.add('hidden');
        }
    }
}
</script>
@endsection
