@extends('layouts.app')

@section('title', 'Editar Rol')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <!-- Alerts -->
    @include('components.alerts')

    <!-- Header -->
    <div class="flex items-center gap-3">
        <a href="{{ route('roles.index') }}"
           class="p-2 hover:bg-slate-100 rounded-lg transition-colors">
            <i class="bi bi-arrow-left text-slate-600"></i>
        </a>
        <div>
            <h1 class="text-xl font-semibold text-slate-900">Editar Rol</h1>
            <p class="text-sm text-slate-500 mt-1">Modifica la información del rol {{ $role->name }}</p>
        </div>
    </div>

    <!-- Formulario -->
    <form method="POST"
          action="{{ route('roles.update', $role) }}"
          class="bg-white rounded-lg border border-slate-200 p-6 space-y-5">
        @csrf
        @method('PUT')

        <!-- Nombre del Rol -->
        <div>
            <label for="name" class="block text-sm font-medium text-slate-700 mb-1.5">
                Nombre del Rol <span class="text-red-500">*</span>
            </label>
            <input type="text"
                   id="name"
                   name="name"
                   value="{{ old('name', $role->name) }}"
                   class="w-full px-4 py-2.5 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none transition @error('name') border-red-400 @enderror"
                   placeholder="Ej: Gerente de Ventas"
                   required>
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
                      placeholder="Describe las responsabilidades de este rol...">{{ old('description', $role->description) }}</textarea>
            @error('description')
                <p class="mt-1.5 text-sm text-red-600 flex items-center gap-1">
                    <i class="bi bi-exclamation-circle"></i>
                    {{ $message }}
                </p>
            @enderror
        </div>

        <!-- Permisos -->
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-3">
                Permisos Asignados
            </label>

            @php
                $permissionsByGroup = $permissions->groupBy('group');
            @endphp

            <div class="space-y-4">
                @foreach($permissionsByGroup as $group => $groupPermissions)
                <div class="border border-slate-200 rounded-lg p-4">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-sm font-semibold text-slate-900">{{ $group }}</h3>
                        <button type="button"
                                onclick="toggleGroup('group-{{ preg_replace('/\s+/', '-', $group) }}')"
                                class="text-xs text-cyan-600 hover:text-cyan-700 font-medium">
                            Seleccionar todos
                        </button>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2" id="group-{{ preg_replace('/\s+/', '-', $group) }}">
                        @foreach($groupPermissions as $permission)
                        <label class="flex items-start gap-2 p-2 hover:bg-slate-50 rounded cursor-pointer">
                            <input type="checkbox"
                                   name="permissions[]"
                                   value="{{ $permission->id }}"
                                   {{ $role->hasPermissionTo($permission->name) ? 'checked' : '' }}
                                   class="w-4 h-4 text-cyan-600 border-slate-300 rounded focus:ring-cyan-500 mt-0.5">
                            <div class="flex-1">
                                <span class="text-sm text-slate-900">{{ $permission->name }}</span>
                                @if($permission->description)
                                <p class="text-xs text-slate-500 mt-0.5">{{ $permission->description }}</p>
                                @endif
                            </div>
                        </label>
                        @endforeach
                    </div>
                </div>
                @endforeach
            </div>

            @error('permissions')
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
                Actualizar Rol
            </button>
            <a href="{{ route('roles.index') }}"
               class="flex-1 px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-medium rounded-lg transition-colors text-center">
                Cancelar
            </a>
        </div>
    </form>
</div>
@endsection

@section('scripts')
<script>
function toggleGroup(groupId) {
    const checkboxes = document.querySelectorAll(`#${groupId} input[type="checkbox"]`);
    const allChecked = Array.from(checkboxes).every(cb => cb.checked);

    checkboxes.forEach(cb => {
        cb.checked = !allChecked;
    });
}
</script>
@endsection
