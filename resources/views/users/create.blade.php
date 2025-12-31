@extends('layouts.app')

@section('title', 'Nuevo Usuario')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <!-- Alerts -->
    @include('components.alerts')

    <!-- Header -->
    <div class="flex items-center gap-3">
        <a href="{{ route('users.index') }}"
           class="p-2 hover:bg-slate-100 rounded-lg transition-colors">
            <i class="bi bi-arrow-left text-slate-600"></i>
        </a>
        <div>
            <h1 class="text-xl font-semibold text-slate-900">Nuevo Usuario</h1>
            <p class="text-sm text-slate-500 mt-1">Crea un nuevo usuario en el sistema</p>
        </div>
    </div>

    <!-- Form -->
    <form method="POST" action="{{ route('users.store') }}" class="bg-white rounded-lg border border-slate-200">
        @csrf

        <div class="p-6 space-y-5">
            @include('users.partials.form')
        </div>

        <div class="flex items-center gap-3 px-6 py-4 bg-slate-50 border-t border-slate-200 rounded-b-lg">
            <button type="submit"
                    class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-cyan-600 hover:bg-cyan-700 text-white text-sm font-medium rounded-lg transition-colors">
                <i class="bi bi-check-lg"></i>
                <span>Crear Usuario</span>
            </button>
            <a href="{{ route('users.index') }}"
               class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-medium rounded-lg transition-colors">
                <span>Cancelar</span>
            </a>
        </div>
    </form>
</div>
@endsection
