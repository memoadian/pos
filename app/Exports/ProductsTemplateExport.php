<?php

namespace App\Exports;

use App\Models\Department;
use App\Models\SaleType;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ProductsTemplateExport implements FromArray, WithHeadings
{
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

    public function array(): array
    {
        $department = Department::orderBy('name')->value('name') ?? 'Abarrotes';
        $saleType = SaleType::where('is_active', true)->orderBy('name')->value('name') ?? 'Pieza';

        return [
            [
                '7501234567890',
                'Producto de ejemplo',
                'apodo, otro apodo',
                $department,
                $saleType,
                10.00,
                15.00,
                13.50,
                12,
                12.00,
                24,
                5,
                'Si',
            ],
        ];
    }
}
