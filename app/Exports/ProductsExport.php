<?php

namespace App\Exports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ProductsExport implements FromCollection, WithHeadings, WithMapping
{
    /**
     * Mismo formato que ProductsTemplateExport pero con los datos reales del
     * catálogo: permite editar en Excel (por ejemplo los mínimos de mayoreo
     * de muchos productos a la vez) y volver a subir el archivo con el
     * importador, que actualiza cada fila por código de barras.
     */
    public function collection()
    {
        return Product::with(['department', 'saleType', 'aliases'])
            ->orderBy('name')
            ->get();
    }

    public function headings(): array
    {
        return [
            'Código Barras',
            'Nombre',
            'Alias',
            'Departamento',
            'Tipo Venta',
            'Costo',
            'Precio Menudeo',
            'Precio Mayoreo',
            'Cantidad Mínima Mayoreo',
            'Precio Super Mayoreo',
            'Cantidad Mínima Super Mayoreo',
            'Stock Mínimo',
            'Activo',
        ];
    }

    /**
     * @return array<int, mixed>
     */
    public function map($product): array
    {
        return [
            $product->barcode,
            $product->name,
            $product->aliases->pluck('alias')->implode(', '),
            $product->department?->name,
            $product->saleType?->name,
            $product->cost,
            $product->price_retail,
            $product->price_wholesale,
            $product->min_wholesale_qty,
            $product->price_super_wholesale,
            $product->min_super_wholesale_qty,
            $product->min_stock,
            $product->is_active ? 'Si' : 'No',
        ];
    }
}
