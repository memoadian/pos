@extends('layouts.app')

@section('title', 'Gestionar Roles')

@section('content')
    <div class="space-y-6">
        <!-- Alerts -->
        @include('components.alerts')

        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-semibold text-slate-900">Gestionar Roles</h1>
                <p class="text-sm text-slate-500 mt-1">Administra los roles y sus permisos en el sistema</p>
            </div>
            <a class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-lg transition-colors"
                href="{{ route('roles.create') }}">
                <i class="bi bi-plus-lg"></i>
                <span>Nuevo Rol</span>
            </a>
        </div>

        <!-- Grid de Roles -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            @forelse($roles as $role)
                <div class="bg-white rounded-lg border border-slate-200 p-5">
                    <!-- Header del card -->
                    <div class="flex items-start justify-between mb-3">
                        <div class="flex-1">
                            <h3 class="text-base font-semibold text-slate-900">{{ $role->name }}</h3>
                            <p class="text-sm text-slate-500 mt-1">{{ $role->description ?? 'Sin descripción' }}</p>
                        </div>
                    </div>

                    <!-- Indicadores -->
                    <div class="flex items-center gap-4 py-3 border-y border-slate-100 my-3">
                        <div class="flex items-center gap-2 text-sm text-slate-600">
                            <i class="bi bi-shield-check text-cyan-600"></i>
                            <span>{{ $role->permissions->count() }} permisos</span>
                        </div>
                        <div class="flex items-center gap-2 text-sm text-slate-600">
                            <i class="bi bi-people text-sky-600"></i>
                            <span>{{ $role->users->count() }} usuarios</span>
                        </div>
                    </div>

                    <!-- Acciones -->
                    <div class="flex items-center gap-2">
                        <a class="flex-1 flex items-center justify-center gap-2 px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-medium rounded-lg transition-colors"
                            href="{{ route('roles.show', $role) }}">
                            <i class="bi bi-eye"></i>
                            <span>Ver</span>
                        </a>
                        <a class="flex-1 flex items-center justify-center gap-2 px-3 py-2 bg-emerald-100 hover:bg-emerald-200 text-emerald-700 text-sm font-medium rounded-lg transition-colors"
                            href="{{ route('roles.edit', $role) }}">
                            <i class="bi bi-pencil"></i>
                            <span>Editar</span>
                        </a>
                        @if (!in_array($role->name, ['Admin', 'Admin']) && $role->users->count() === 0)
                            <button
                                class="flex-1 flex items-center justify-center gap-2 px-3 py-2 bg-red-100 hover:bg-red-200 text-red-700 text-sm font-medium rounded-lg transition-colors"
                                onclick="confirmDelete({{ $role->id }})">
                                <i class="bi bi-trash"></i>
                                <span>Eliminar</span>
                            </button>
                        @endif
                    </div>
                </div>
            @empty
                <div class="col-span-2 bg-white rounded-lg border border-slate-200 p-12 text-center">
                    <i class="bi bi-shield-x text-4xl text-slate-300"></i>
                    <p class="text-slate-600 mt-3">No hay roles registrados</p>
                </div>
            @endforelse
        </div>
    </div>

@endsection

@section('scripts')
    <script>
        function confirmDelete(roleId) {
            ConfirmModal.confirmDelete({
                action: `/roles/${roleId}`,
                title: 'Eliminar rol',
                message: '¿Estás seguro de eliminar este rol? Esta acción no se puede deshacer.',
            });
        }
    </script>
@endsection
