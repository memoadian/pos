<?php

namespace App\Imports;

use App\Models\Department;
use App\Models\Product;
use App\Models\SaleType;
use Illuminate\Support\Collection as BaseCollection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ProductsImport implements ToCollection, WithHeadingRow
{
    public int $created = 0;

    public int $updated = 0;

    /** @var array<int, array{row: int, errors: array<int, string>}> */
    public array $errors = [];

    /** @var array<string, int> Nombre de departamento (normalizado) => id */
    private array $departments;

    /** @var array<string, int> Nombre de tipo de venta (normalizado) => id */
    private array $saleTypes;

    public function __construct()
    {
        $this->departments = Department::pluck('id', 'name')
            ->mapWithKeys(fn ($id, $name) => [$this->normalize($name) => $id])
            ->all();

        $this->saleTypes = SaleType::pluck('id', 'name')
            ->mapWithKeys(fn ($id, $name) => [$this->normalize($name) => $id])
            ->all();
    }

    /** @var array<int, string> Columnas numericas que pueden venir con formato de moneda (ej. "$44.00") */
    private const CURRENCY_COLUMNS = ['costo', 'precio_menudeo', 'precio_mayoreo', 'precio_super_mayoreo'];

    public function collection(BaseCollection $rows): void
    {
        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2; // +1 por el encabezado, +1 porque el indice inicia en 0
            $row = $row->map(fn ($value) => is_string($value) ? trim($value) : $value);

            if ($row->filter()->isEmpty()) {
                continue;
            }

            // Excel guarda los codigos de barras puramente numericos (ej. "7501234567890")
            // como int/float en vez de string, lo que rompe la validacion y la comparacion.
            if ($row['codigo_barras'] !== null) {
                $row->put('codigo_barras', trim((string) $row['codigo_barras']));
            }

            // Exportaciones de otros sistemas suelen guardar los precios como texto con
            // simbolo de moneda (ej. "$44.00"), lo que rompe la validacion "numeric".
            foreach (self::CURRENCY_COLUMNS as $column) {
                if (is_string($row[$column] ?? null)) {
                    $row->put($column, str_replace(['$', ',', ' '], '', $row[$column]));
                }
            }

            $errors = $this->validateRow($row);

            if (! empty($errors)) {
                $this->errors[] = ['row' => $rowNumber, 'errors' => $errors];

                continue;
            }

            $departmentId = $this->departments[$this->normalize($row['departamento'])];
            $saleTypeId = $this->saleTypes[$this->normalize($row['tipo_venta'])];
            $baseUnit = SaleType::find($saleTypeId)->base_unit;

            // Precio en 0 significa "sin descuento" para ese nivel: se guarda el precio
            // tal cual pero se ignora la cantidad minima para que el nivel nunca se active.
            $wholesaleQty = $row['cantidad_minima_mayoreo'] ?: null;
            if (! ($row['precio_mayoreo'] > 0)) {
                $wholesaleQty = null;
            }

            $superWholesaleQty = $row['cantidad_minima_super_mayoreo'] ?: null;
            if (! (($row['precio_super_mayoreo'] ?: 0) > 0)) {
                $superWholesaleQty = null;
            }

            $attributes = [
                'department_id' => $departmentId,
                'name' => $row['nombre'],
                'sale_type_id' => $saleTypeId,
                'unit_base' => $baseUnit,
                'min_stock' => $row['stock_minimo'] ?: null,
                'price_retail' => $row['precio_menudeo'],
                'price_wholesale' => $row['precio_mayoreo'],
                'price_super_wholesale' => $row['precio_super_mayoreo'] ?: 0,
                'cost' => $row['costo'],
                'min_wholesale_qty' => $wholesaleQty,
                'min_super_wholesale_qty' => $superWholesaleQty,
                'is_active' => $this->parseBoolean($row['activo']),
            ];

            $barcode = $row['codigo_barras'];

            if (Product::where('barcode', $barcode)->exists()) {
                Product::where('barcode', $barcode)->update($attributes);
                $this->updated++;
            } else {
                Product::create($attributes + ['barcode' => $barcode]);
                $this->created++;
            }
        }
    }

    /**
     * @return array<int, string>
     */
    private function validateRow(BaseCollection $row): array
    {
        $validator = Validator::make($row->all(), [
            'codigo_barras' => ['required', 'string', 'max:255'],
            'nombre' => ['required', 'string', 'max:255'],
            'departamento' => ['required', 'string'],
            'tipo_venta' => ['required', 'string'],
            'costo' => ['required', 'numeric', 'min:0'],
            'precio_menudeo' => ['required', 'numeric', 'min:0'],
            'precio_mayoreo' => ['required', 'numeric', 'min:0'],
            'precio_super_mayoreo' => ['nullable', 'numeric', 'min:0'],
            'cantidad_minima_mayoreo' => ['nullable', 'integer', 'min:1'],
            'cantidad_minima_super_mayoreo' => ['nullable', 'integer', 'min:1'],
            'stock_minimo' => ['nullable', 'numeric', 'min:0'],
        ], [
            'codigo_barras.required' => 'El código de barras es obligatorio',
            'nombre.required' => 'El nombre es obligatorio',
            'departamento.required' => 'El departamento es obligatorio',
            'tipo_venta.required' => 'El tipo de venta es obligatorio',
            'costo.required' => 'El costo es obligatorio',
            'precio_menudeo.required' => 'El precio al menudeo es obligatorio',
            'precio_mayoreo.required' => 'El precio al mayoreo es obligatorio',
        ]);

        if ($validator->fails()) {
            return $validator->errors()->all();
        }

        $errors = [];

        if (! isset($this->departments[$this->normalize($row['departamento'])])) {
            $errors[] = "El departamento \"{$row['departamento']}\" no existe";
        }

        if (! isset($this->saleTypes[$this->normalize($row['tipo_venta'])])) {
            $errors[] = "El tipo de venta \"{$row['tipo_venta']}\" no existe";
        }

        if (
            ! empty($row['cantidad_minima_super_mayoreo'])
            && ! empty($row['cantidad_minima_mayoreo'])
            && $row['cantidad_minima_super_mayoreo'] <= $row['cantidad_minima_mayoreo']
        ) {
            $errors[] = 'La cantidad mínima para super mayoreo debe ser mayor que la de mayoreo';
        }

        return $errors;
    }

    private function normalize(string $value): string
    {
        return Str::lower(trim($value));
    }

    private function parseBoolean(mixed $value): bool
    {
        if ($value === null || $value === '') {
            return true;
        }

        return in_array(Str::lower(trim((string) $value)), ['1', 'si', 'sí', 'true', 'activo'], true);
    }
}
