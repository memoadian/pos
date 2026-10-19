<?php

namespace App\Exports;

use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class InventoryExport implements FromQuery, WithColumnFormatting, WithColumnWidths, WithHeadings, WithMapping
{
    /**
     * Recibe la misma consulta filtrada que la pantalla para que el archivo
     * descargado se pueda editar y volver a subir sin cambiar de formato.
     */
    public function __construct(private Builder $query) {}

    public function query(): Builder
    {
        return $this->query;
    }

    public function headings(): array
    {
        return [
            'Código Barras',
            'Producto',
            'Departamento',
            'Sucursal',
            'Unidad',
            'Stock Actual',
        ];
    }

    /**
     * @return array<int, mixed>
     */
    public function map($inventory): array
    {
        return [
            (string) $inventory->product->barcode,
            $inventory->product->name,
            $inventory->product->department?->name,
            $inventory->branch?->name,
            $inventory->product->unit_base,
            (float) $inventory->stock_quantity,
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 20,
            'B' => 42,
            'C' => 24,
            'D' => 22,
            'E' => 12,
            'F' => 16,
        ];
    }

    public function columnFormats(): array
    {
        return [
            'F' => NumberFormat::FORMAT_NUMBER_00,
        ];
    }
}
