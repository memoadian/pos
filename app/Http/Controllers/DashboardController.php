<?php

namespace App\Http\Controllers;

use App\Models\CashRegister;
use App\Models\Inventory;
use App\Models\Sale;
use App\Models\SaleItem;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $branchId = $user->branch_id;

        // Ventas de hoy
        $today = Carbon::today();
        $yesterday = Carbon::yesterday();

        $salesTodayQuery = Sale::whereDate('created_at', $today);
        $salesYesterdayQuery = Sale::whereDate('created_at', $yesterday);

        // Filtrar por sucursal si el usuario tiene una asignada
        if ($branchId) {
            $salesTodayQuery->where('branch_id', $branchId);
            $salesYesterdayQuery->where('branch_id', $branchId);
        }

        $salesToday = $salesTodayQuery->sum('total');
        $salesYesterday = $salesYesterdayQuery->sum('total');

        // Calcular porcentaje de cambio
        $salesChange = 0;
        if ($salesYesterday > 0) {
            $salesChange = (($salesToday - $salesYesterday) / $salesYesterday) * 100;
        } elseif ($salesToday > 0) {
            $salesChange = 100; // Si hoy hay ventas pero ayer no, es 100% de incremento
        }

        // Productos vendidos hoy (suma de cantidades de sale_items)
        $productsSoldToday = SaleItem::whereHas('sale', function ($query) use ($today, $branchId) {
            $query->whereDate('created_at', $today);
            if ($branchId) {
                $query->where('branch_id', $branchId);
            }
        })->sum('quantity');

        // Caja abierta del usuario
        $cashRegister = CashRegister::where('user_id', $user->id)
            ->where('status', 'open')
            ->first();

        $cashAmount = 0;
        $digitalAmount = 0;
        $totalInCash = 0;

        if ($cashRegister) {
            // Efectivo: monto de apertura + ventas en efectivo + ingresos aprobados - retiros aprobados
            $cashAmount = $cashRegister->expected_amount;

            // Digital: ventas con tarjeta + transferencias
            $digitalAmount = ($cashRegister->card_sales ?? 0) + ($cashRegister->transfer_sales ?? 0);

            // Total en caja
            $totalInCash = $cashAmount + $digitalAmount;
        }

        // Stock bajo - Productos con stock crítico (menos de 10 unidades o sin stock)
        $lowStockThreshold = 10;
        $lowStockQuery = Inventory::where('stock_quantity', '<', $lowStockThreshold);

        // Filtrar por sucursal si el usuario tiene una asignada
        if ($branchId) {
            $lowStockQuery->where('branch_id', $branchId);
        }

        $lowStockCount = $lowStockQuery->count();

        // Actividad reciente - Últimas 10 ventas del día
        $recentSalesQuery = Sale::with('user')
            ->whereDate('created_at', $today)
            ->orderBy('created_at', 'desc')
            ->limit(10);

        // Filtrar por sucursal si el usuario tiene una asignada
        if ($branchId) {
            $recentSalesQuery->where('branch_id', $branchId);
        }

        $recentSales = $recentSalesQuery->get();

        return view('dashboard', [
            'salesToday' => $salesToday,
            'salesYesterday' => $salesYesterday,
            'salesChange' => $salesChange,
            'productsSoldToday' => $productsSoldToday,
            'totalInCash' => $totalInCash,
            'cashAmount' => $cashAmount,
            'digitalAmount' => $digitalAmount,
            'lowStockCount' => $lowStockCount,
            'recentSales' => $recentSales,
        ]);
    }
}
