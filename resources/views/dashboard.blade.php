@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="space-y-6">
    <!-- Page Header -->
    <div>
        <h1 class="text-3xl font-bold text-slate-800">Dashboard</h1>
        <p class="text-slate-600 mt-1">Resumen de tu actividad diaria</p>
    </div>

    <!-- Stats Cards Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Card 1: Ventas del Día -->
        <div class="bg-gradient-to-br from-cyan-50 to-sky-50 rounded-xl shadow-md p-6 border border-cyan-200 hover:shadow-lg transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-600 font-medium">Ventas del Día</p>
                    <p class="text-3xl font-bold text-cyan-700 mt-2">$0.00</p>
                </div>
                <div class="h-12 w-12 bg-cyan-500 rounded-full flex items-center justify-center flex-shrink-0">
                    <i class="bi bi-cash-coin text-white text-xl"></i>
                </div>
            </div>
            <p class="text-xs text-slate-500 mt-3">
                <span class="text-green-600 font-semibold">+0%</span> vs ayer
            </p>
        </div>

        <!-- Card 2: Productos Vendidos -->
        <div class="bg-gradient-to-br from-sky-50 to-blue-50 rounded-xl shadow-md p-6 border border-sky-200 hover:shadow-lg transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-600 font-medium">Productos Vendidos</p>
                    <p class="text-3xl font-bold text-sky-700 mt-2">0</p>
                </div>
                <div class="h-12 w-12 bg-sky-500 rounded-full flex items-center justify-center flex-shrink-0">
                    <i class="bi bi-box-seam text-white text-xl"></i>
                </div>
            </div>
            <p class="text-xs text-slate-500 mt-3">Artículos hoy</p>
        </div>

        <!-- Card 3: Caja Actual -->
        <div class="bg-gradient-to-br from-emerald-50 to-green-50 rounded-xl shadow-md p-6 border border-emerald-200 hover:shadow-lg transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-600 font-medium">Caja Actual</p>
                    <p class="text-3xl font-bold text-emerald-700 mt-2">$0.00</p>
                </div>
                <div class="h-12 w-12 bg-emerald-500 rounded-full flex items-center justify-center flex-shrink-0">
                    <i class="bi bi-cash-stack text-white text-xl"></i>
                </div>
            </div>
            <p class="text-xs text-slate-500 mt-3">Efectivo + Digital</p>
        </div>

        <!-- Card 4: Stock Bajo -->
        <div class="bg-gradient-to-br from-amber-50 to-orange-50 rounded-xl shadow-md p-6 border border-amber-200 hover:shadow-lg transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-600 font-medium">Alerta de Stock</p>
                    <p class="text-3xl font-bold text-amber-700 mt-2">0</p>
                </div>
                <div class="h-12 w-12 bg-amber-500 rounded-full flex items-center justify-center flex-shrink-0">
                    <i class="bi bi-exclamation-triangle text-white text-xl"></i>
                </div>
            </div>
            <p class="text-xs text-slate-500 mt-3">Stock bajo</p>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="bg-white rounded-xl shadow-md p-6 border border-slate-200">
        <h2 class="text-lg font-semibold text-slate-800 mb-4">Acciones Rápidas</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <a href="#" class="flex flex-col items-center justify-center p-4 bg-gradient-to-br from-cyan-50 to-sky-50 rounded-lg border border-cyan-200 hover:shadow-lg hover:border-cyan-300 transition-all">
                <div class="h-12 w-12 bg-cyan-500 rounded-full flex items-center justify-center mb-2">
                    <i class="bi bi-plus-circle text-white text-xl"></i>
                </div>
                <p class="text-sm font-medium text-slate-800 text-center">Nueva Venta</p>
            </a>

            <a href="#" class="flex flex-col items-center justify-center p-4 bg-gradient-to-br from-sky-50 to-blue-50 rounded-lg border border-sky-200 hover:shadow-lg hover:border-sky-300 transition-all">
                <div class="h-12 w-12 bg-sky-500 rounded-full flex items-center justify-center mb-2">
                    <i class="bi bi-plus-square text-white text-xl"></i>
                </div>
                <p class="text-sm font-medium text-slate-800 text-center">Nuevo Producto</p>
            </a>

            <a href="#" class="flex flex-col items-center justify-center p-4 bg-gradient-to-br from-emerald-50 to-green-50 rounded-lg border border-emerald-200 hover:shadow-lg hover:border-emerald-300 transition-all">
                <div class="h-12 w-12 bg-emerald-500 rounded-full flex items-center justify-center mb-2">
                    <i class="bi bi-arrow-left-right text-white text-xl"></i>
                </div>
                <p class="text-sm font-medium text-slate-800 text-center">Movimiento</p>
            </a>

            <a href="#" class="flex flex-col items-center justify-center p-4 bg-gradient-to-br from-purple-50 to-violet-50 rounded-lg border border-purple-200 hover:shadow-lg hover:border-purple-300 transition-all">
                <div class="h-12 w-12 bg-purple-500 rounded-full flex items-center justify-center mb-2">
                    <i class="bi bi-graph-up text-white text-xl"></i>
                </div>
                <p class="text-sm font-medium text-slate-800 text-center">Reporte</p>
            </a>
        </div>
    </div>

    <!-- User Information -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white rounded-xl shadow-md p-6 border border-slate-200">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm text-slate-600 font-medium">Usuario</p>
                    <p class="text-2xl font-bold text-slate-900 mt-2">{{ auth()->user()->username }}</p>
                </div>
                <div class="h-10 w-10 bg-cyan-100 rounded-lg flex items-center justify-center">
                    <i class="bi bi-person text-cyan-700 text-lg"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-md p-6 border border-slate-200">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm text-slate-600 font-medium">Rol</p>
                    <p class="text-2xl font-bold text-slate-900 mt-2 capitalize">{{ auth()->user()->role }}</p>
                </div>
                <div class="h-10 w-10 bg-sky-100 rounded-lg flex items-center justify-center">
                    <i class="bi bi-shield-check text-sky-700 text-lg"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-md p-6 border border-slate-200">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm text-slate-600 font-medium">Estado</p>
                    <p class="text-2xl font-bold text-emerald-600 mt-2">Activo</p>
                </div>
                <div class="h-10 w-10 bg-emerald-100 rounded-lg flex items-center justify-center">
                    <i class="bi bi-check-circle text-emerald-700 text-lg"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="bg-white rounded-xl shadow-md overflow-hidden border border-slate-200">
        <div class="bg-gradient-to-r from-cyan-500 to-sky-500 px-6 py-4">
            <h2 class="text-lg font-semibold text-white flex items-center gap-2">
                <i class="bi bi-clock-history"></i>
                Actividad Reciente
            </h2>
        </div>
        <div class="p-6">
            <p class="text-slate-600 text-center py-8">No hay actividad reciente</p>
            <p class="text-sm text-slate-500 text-center">Las ventas y movimientos aparecerán aquí en tiempo real</p>
        </div>
    </div>
</div>
@endsection
