@extends('layouts.app')

@section('title', 'Detalle del Rol')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <!-- Alerts -->
    @include('components.alerts')

    <!-- Header -->
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('roles.index') }}"
               class="p-2 hover:bg-slate-100 rounded-lg transition-colors">
                <i class="bi bi-arrow-left text-slate-600"></i>
            </a>
            <div>
                <h1 class="text-xl font-semibold text-slate-900">{{ $role->name }}</h1>
                <p class="text-sm text-slate-500 mt-1">{{ $role->description ?? 'Sin descripción' }}</p>
            </div>
        </div>
        @if(!in_array($role->name, ['Admin', 'Super Admin']))
        <a href="{{ route('roles.edit', $role) }}"
           class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-lg transition-colors">
            <i class="bi bi-pencil"></i>
            <span>Editar</span>
        </a>
        @endif
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-2 gap-4">
        <div class="bg-white rounded-lg border border-slate-200 p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-cyan-50 rounded-lg flex items-center justify-center">
                    <i class="bi bi-shield-check text-cyan-600 text-lg"></i>
                </div>
                <div>
                    <p class="text-xs font-medium text-slate-500 uppercase">Permisos</p>
                    <p class="text-xl font-semibold text-slate-900">{{ $role->permissions->count() }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg border border-slate-200 p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-sky-50 rounded-lg flex items-center justify-center">
                    <i class="bi bi-people text-sky-600 text-lg"></i>
                </div>
                <div>
                    <p class="text-xs font-medium text-slate-500 uppercase">Usuarios</p>
                    <p class="text-xl font-semibold text-slate-900">{{ $role->users->count() }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Permisos Asignados -->
    <div class="bg-white rounded-lg border border-slate-200">
        <div class="px-5 py-4 border-b border-slate-200">
            <h2 class="text-sm font-semibold text-slate-900">Permisos Asignados</h2>
        </div>
        <div class="p-5">
            @php
                $permissionsByGroup = $role->permissions->groupBy('group');
            @endphp

            @if($permissionsByGroup->count() > 0)
            <div class="space-y-4">
                @foreach($permissionsByGroup as $group => $permissions)
                <div>
                    <h3 class="text-sm font-medium text-slate-900 mb-2">{{ $group }}</h3>
                    <div class="flex flex-wrap gap-2">
                        @foreach($permissions as $permission)
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-100 text-slate-700 rounded-lg text-sm">
                            <i class="bi bi-check-circle-fill text-emerald-600 text-xs"></i>
                            {{ $permission->name }}
                        </span>
                        @endforeach
                    </div>
                </div>
                @endforeach
            </div>
            @else
            <div class="text-center py-8">
                <i class="bi bi-shield-x text-3xl text-slate-300"></i>
                <p class="text-sm text-slate-600 mt-2">No hay permisos asignados a este rol</p>
            </div>
            @endif
        </div>
    </div>

    <!-- Usuarios con este Rol -->
    <div class="bg-white rounded-lg border border-slate-200">
        <div class="px-5 py-4 border-b border-slate-200">
            <h2 class="text-sm font-semibold text-slate-900">Usuarios con este Rol</h2>
        </div>
        <div class="p-5">
            @if($role->users->count() > 0)
            <div class="space-y-2">
                @foreach($role->users as $user)
                <div class="flex items-center gap-3 p-3 hover:bg-slate-50 rounded-lg transition-colors">
                    <div class="w-8 h-8 bg-cyan-600 rounded-full flex items-center justify-center text-white text-sm font-medium">
                        {{ strtoupper(substr($user->name, 0, 1)) }}
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-medium text-slate-900">{{ $user->name }}</p>
                        <p class="text-xs text-slate-500">{{ $user->email }}</p>
                    </div>
                    <span class="text-xs px-2 py-1 rounded-full {{ $user->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700' }}">
                        {{ $user->is_active ? 'Activo' : 'Inactivo' }}
                    </span>
                </div>
                @endforeach
            </div>
            @else
            <div class="text-center py-8">
                <i class="bi bi-people text-3xl text-slate-300"></i>
                <p class="text-sm text-slate-600 mt-2">No hay usuarios asignados a este rol</p>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
