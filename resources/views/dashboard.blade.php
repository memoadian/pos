@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div>
        <h1 class="text-xl font-semibold text-slate-900">Dashboard</h1>
        <p class="text-sm text-slate-500 mt-1">Resumen de actividad del día</p>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Ventas -->
        <div class="bg-white rounded-lg border border-slate-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Ventas Hoy</p>
                    <p class="text-2xl font-semibold text-slate-900 mt-1">$0.00</p>
                </div>
                <div class="w-10 h-10 bg-cyan-50 rounded-lg flex items-center justify-center">
                    <i class="bi bi-currency-dollar text-cyan-600 text-lg"></i>
                </div>
            </div>
            <p class="text-xs text-slate-500 mt-2">
                <span class="text-emerald-600">+0%</span> vs ayer
            </p>
        </div>

        <!-- Productos -->
        <div class="bg-white rounded-lg border border-slate-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Productos</p>
                    <p class="text-2xl font-semibold text-slate-900 mt-1">0</p>
                </div>
                <div class="w-10 h-10 bg-sky-50 rounded-lg flex items-center justify-center">
                    <i class="bi bi-box-seam text-sky-600 text-lg"></i>
                </div>
            </div>
            <p class="text-xs text-slate-500 mt-2">Vendidos hoy</p>
        </div>

        <!-- Caja -->
        <div class="bg-white rounded-lg border border-slate-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">En Caja</p>
                    <p class="text-2xl font-semibold text-slate-900 mt-1">$0.00</p>
                </div>
                <div class="w-10 h-10 bg-emerald-50 rounded-lg flex items-center justify-center">
                    <i class="bi bi-cash-stack text-emerald-600 text-lg"></i>
                </div>
            </div>
            <p class="text-xs text-slate-500 mt-2">Efectivo + Digital</p>
        </div>

        <!-- Alertas -->
        <div class="bg-white rounded-lg border border-slate-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Stock Bajo</p>
                    <p class="text-2xl font-semibold text-slate-900 mt-1">0</p>
                </div>
                <div class="w-10 h-10 bg-amber-50 rounded-lg flex items-center justify-center">
                    <i class="bi bi-exclamation-triangle text-amber-600 text-lg"></i>
                </div>
            </div>
            <p class="text-xs text-slate-500 mt-2">Productos</p>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="bg-white rounded-lg border border-slate-200 p-5">
        <h2 class="text-sm font-semibold text-slate-900 mb-4">Acciones Rápidas</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <a href="#" class="flex items-center gap-3 p-3 rounded-lg border border-slate-200 hover:border-cyan-300 hover:bg-cyan-50/50 transition-colors group">
                <div class="w-9 h-9 bg-cyan-100 rounded-lg flex items-center justify-center group-hover:bg-cyan-200 transition-colors">
                    <i class="bi bi-plus-lg text-cyan-700"></i>
                </div>
                <span class="text-sm font-medium text-slate-700">Nueva Venta</span>
            </a>

            <a href="#" class="flex items-center gap-3 p-3 rounded-lg border border-slate-200 hover:border-sky-300 hover:bg-sky-50/50 transition-colors group">
                <div class="w-9 h-9 bg-sky-100 rounded-lg flex items-center justify-center group-hover:bg-sky-200 transition-colors">
                    <i class="bi bi-box-seam text-sky-700"></i>
                </div>
                <span class="text-sm font-medium text-slate-700">Productos</span>
            </a>

            <a href="#" class="flex items-center gap-3 p-3 rounded-lg border border-slate-200 hover:border-emerald-300 hover:bg-emerald-50/50 transition-colors group">
                <div class="w-9 h-9 bg-emerald-100 rounded-lg flex items-center justify-center group-hover:bg-emerald-200 transition-colors">
                    <i class="bi bi-arrow-left-right text-emerald-700"></i>
                </div>
                <span class="text-sm font-medium text-slate-700">Inventario</span>
            </a>

            <a href="#" class="flex items-center gap-3 p-3 rounded-lg border border-slate-200 hover:border-violet-300 hover:bg-violet-50/50 transition-colors group">
                <div class="w-9 h-9 bg-violet-100 rounded-lg flex items-center justify-center group-hover:bg-violet-200 transition-colors">
                    <i class="bi bi-file-earmark-text text-violet-700"></i>
                </div>
                <span class="text-sm font-medium text-slate-700">Reportes</span>
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Info del Usuario -->
        <div class="bg-white rounded-lg border border-slate-200 p-5">
            <h2 class="text-sm font-semibold text-slate-900 mb-4">Mi Cuenta</h2>
            <div class="space-y-3">
                <div class="flex items-center justify-between py-2 border-b border-slate-100">
                    <span class="text-sm text-slate-500">Usuario</span>
                    <span class="text-sm font-medium text-slate-900">{{ auth()->user()->username }}</span>
                </div>
                <div class="flex items-center justify-between py-2 border-b border-slate-100">
                    <span class="text-sm text-slate-500">Rol</span>
                    <span class="text-sm font-medium text-slate-900 capitalize">{{ auth()->user()->role }}</span>
                </div>
                <div class="flex items-center justify-between py-2">
                    <span class="text-sm text-slate-500">Estado</span>
                    <span class="inline-flex items-center gap-1.5 text-sm font-medium text-emerald-700">
                        <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full"></span>
                        Activo
                    </span>
                </div>
            </div>
        </div>

        <!-- Actividad Reciente -->
        <div class="lg:col-span-2 bg-white rounded-lg border border-slate-200">
            <div class="px-5 py-4 border-b border-slate-200">
                <h2 class="text-sm font-semibold text-slate-900">Actividad Reciente</h2>
            </div>
            <div class="p-5">
                <div class="text-center py-8">
                    <div class="w-12 h-12 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-3">
                        <i class="bi bi-clock-history text-slate-400 text-xl"></i>
                    </div>
                    <p class="text-sm text-slate-600">No hay actividad reciente</p>
                    <p class="text-xs text-slate-400 mt-1">Las ventas aparecerán aquí</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
