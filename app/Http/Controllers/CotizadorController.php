<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CotizadorController extends Controller
{
    /**
     * Herramienta de cálculo: arma una lista de productos y sobre el costo
     * de compra aplica el % de ganancia que el usuario quiera (0-100%) para
     * ver el precio de venta sugerido. No persiste nada, es solo cálculo.
     */
    public function index()
    {
        return view('cotizador.index');
    }

    /**
     * Búsqueda liviana de productos para el cotizador: solo lo necesario
     * para armar una línea (nombre y costo), sin stock ni datos de sucursal.
     */
    public function searchProducts(Request $request): JsonResponse
    {
        $request->validate([
            'query' => 'required|string|min:1',
        ]);

        $products = Product::where('is_active', true)
            ->search($request->input('query'))
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'name', 'barcode', 'cost', 'price_retail']);

        return response()->json(['products' => $products]);
    }
}
