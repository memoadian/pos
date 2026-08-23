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

    /** Filas con errores, incluidas las que ya no se detallan por el tope de reporte */
    public int $errorRows = 0;

    /** @var array<int, array{row: int, errors: array<int, string>}> */
    public array $errors = [];

    /** @var array<string, int> Motivo del error => cuantas filas lo tuvieron */
    public array $errorSummary = [];

    /** @var array<string, int> Nombre de departamento (normalizado) => id */
    private array $departments = [];

    /** @var array<string, string> Nombre de departamento (normalizado) => nombre original */
    private array $departmentNames = [];

    /** @var array<string, int> Nombre de tipo de venta (normalizado) => id */
    private array $saleTypes = [];

    /** @var array<string, string> Nombre de tipo de venta (normalizado) => nombre original */
    private array $saleTypeNames = [];

    /** @var array<int, string> id de tipo de venta => unidad base */
    private array $baseUnits = [];

    public function __construct()
    {
        foreach (Department::pluck('id', 'name') as $name => $id) {
            $this->departments[$this->normalize($name)] = $id;
            $this->departmentNames[$this->normalize($name)] = $name;
        }

        foreach (SaleType::pluck('id', 'name') as $name => $id) {
            $this->saleTypes[$this->normalize($name)] = $id;
            $this->saleTypeNames[$this->normalize($name)] = $name;
        }

        $this->baseUnits = SaleType::pluck('base_unit', 'id')->all();
    }

    /** Un archivo entero mal armado genera una fila de error por producto: se detallan las primeras */
    private const MAX_REPORTED_ROWS = 500;

    /** @var array<int, string> Columnas numericas que pueden venir con formato de moneda (ej. "$44.00") */
    private const CURRENCY_COLUMNS = ['costo', 'precio_menudeo', 'precio_mayoreo', 'precio_super_mayoreo'];

    /** @var array<string, string> Encabezado normalizado => etiqueta de la plantilla */
    private const COLUMN_LABELS = [
        'codigo_barras' => 'Código Barras',
        'nombre' => 'Nombre',
        'departamento' => 'Departamento',
        'tipo_venta' => 'Tipo Venta',
        'costo' => 'Costo',
        'precio_menudeo' => 'Precio Menudeo',
        'precio_mayoreo' => 'Precio Mayoreo',
        'cantidad_minima_mayoreo' => 'Cantidad Mínima Mayoreo',
        'precio_super_mayoreo' => 'Precio Super Mayoreo',
        'cantidad_minima_super_mayoreo' => 'Cantidad Mínima Super Mayoreo',
        'stock_minimo' => 'Stock Mínimo',
        'activo' => 'Activo',
    ];

    /** @var array<int, string> Columnas sin las que una fila no se puede dar de alta */
    private const REQUIRED_COLUMNS = [
        'codigo_barras', 'nombre', 'departamento', 'tipo_venta', 'costo', 'precio_menudeo', 'precio_mayoreo',
    ];

    public function collection(BaseCollection $rows): void
    {
        if ($rows->isEmpty()) {
            return;
        }

        // Un archivo con otros encabezados (una exportacion de otro sistema, por ejemplo)
        // no trae las claves que espera el resto del metodo: se avisa en vez de reventar.
        $missing = array_diff(self::REQUIRED_COLUMNS, array_keys($rows->first()->all()));

        if (! empty($missing)) {
            $labels = implode(', ', array_map(fn ($column) => self::COLUMN_LABELS[$column], $missing));

            $this->addError(1, [[
                "El archivo no coincide con la plantilla. Faltan las columnas: {$labels}. "
                    .'Descarga la plantilla y vuelve a intentarlo.',
                'El archivo no coincide con la plantilla',
            ]]);

            return;
        }

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2; // +1 por el encabezado, +1 porque el indice inicia en 0
            $row = $row->map(fn ($value) => is_string($value) ? trim($value) : $value);

            if ($row->filter()->isEmpty()) {
                continue;
            }

            // Excel guarda los codigos de barras puramente numericos (ej. "7501234567890")
            // como int/float en vez de string, lo que rompe la validacion y la comparacion.
            if ($row->get('codigo_barras') !== null) {
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
                $this->addError($rowNumber, $errors);

                continue;
            }

            $departmentId = $this->departments[$this->normalize($row['departamento'])];
            $saleTypeId = $this->saleTypes[$this->normalize($row['tipo_venta'])];

            // Precio en 0 significa "sin descuento" para ese nivel: se guarda el precio
            // tal cual pero se ignora la cantidad minima para que el nivel nunca se active.
            $wholesaleQty = $row->get('cantidad_minima_mayoreo') ?: null;
            if (! ($row['precio_mayoreo'] > 0)) {
                $wholesaleQty = null;
            }

            $superWholesaleQty = $row->get('cantidad_minima_super_mayoreo') ?: null;
            if (! (($row->get('precio_super_mayoreo') ?: 0) > 0)) {
                $superWholesaleQty = null;
            }

            $attributes = [
                'department_id' => $departmentId,
                'name' => $row['nombre'],
                'sale_type_id' => $saleTypeId,
                'unit_base' => $this->baseUnits[$saleTypeId],
                'min_stock' => $row->get('stock_minimo') ?: null,
                'price_retail' => $row['precio_menudeo'],
                'price_wholesale' => $row['precio_mayoreo'],
                'price_super_wholesale' => $row->get('precio_super_mayoreo') ?: 0,
                'cost' => $row['costo'],
                'min_wholesale_qty' => $wholesaleQty,
                'min_super_wholesale_qty' => $superWholesaleQty,
                'is_active' => $this->parseBoolean($row->get('activo')),
            ];

            $barcode = $row['codigo_barras'];

            if (Product::where('barcode', $barcode)->exists()) {
                Product::where('barcode', $barcode)->update($attributes);

                // La plantilla maneja un solo tipo de venta (el principal); los
                // tipos adicionales se administran desde la edicion del producto.
                // Si el principal nuevo ya existia como adicional, esa fila sobra.
                Product::where('barcode', $barcode)
                    ->first()
                    ?->productSaleTypes()
                    ->where('sale_type_id', $saleTypeId)
                    ->delete();

                $this->updated++;
            } else {
                Product::create($attributes + ['barcode' => $barcode]);
                $this->created++;
            }
        }
    }

    /**
     * Guarda el detalle de una fila invalida y suma su motivo al resumen.
     *
     * @param  array<int, array{0: string, 1: string}>  $errors  Pares [mensaje, motivo]
     */
    private function addError(int $rowNumber, array $errors): void
    {
        $messages = [];

        foreach ($errors as [$message, $reason]) {
            $messages[] = $message;
            $this->errorSummary[$reason] = ($this->errorSummary[$reason] ?? 0) + 1;
        }

        $this->errorRows++;

        // El detalle fila por fila se acota: un archivo de 20 mil renglones mal
        // armados no aporta mas informacion que los primeros cientos.
        if (count($this->errors) < self::MAX_REPORTED_ROWS) {
            $this->errors[] = ['row' => $rowNumber, 'errors' => $messages];
        }
    }

    /**
     * @return array<int, array{0: string, 1: string}> Pares [mensaje, motivo]
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
            'required' => 'Falta :attribute: la columna es obligatoria y vino vacía',
            'string' => ':attribute debe ser texto',
            'max' => ':attribute no puede pasar de :max caracteres (se recibió ":input")',
            'numeric' => ':attribute debe ser un número, sin letras ni símbolos (se recibió ":input")',
            'integer' => ':attribute debe ser un número entero, sin decimales (se recibió ":input")',
            'min' => ':attribute no puede ser negativo (se recibió ":input")',
            'cantidad_minima_mayoreo.min' => ':attribute debe ser 1 o más (se recibió ":input")',
            'cantidad_minima_super_mayoreo.min' => ':attribute debe ser 1 o más (se recibió ":input")',
        ], self::COLUMN_LABELS);

        if ($validator->fails()) {
            return array_map(
                // El motivo agrupa filas distintas que fallan por lo mismo, asi que
                // se le quita el valor concreto que solo aplica a esta fila.
                fn ($message) => [$message, preg_replace('/ \(se recibió .*\)$/u', '', $message)],
                $validator->errors()->all(),
            );
        }

        $errors = [];

        if (! isset($this->departments[$this->normalize($row['departamento'])])) {
            $errors[] = [
                "El departamento \"{$row['departamento']}\" no existe en el sistema."
                    .$this->suggestion($row['departamento'], $this->departmentNames),
                'Departamento no encontrado en el sistema',
            ];
        }

        if (! isset($this->saleTypes[$this->normalize($row['tipo_venta'])])) {
            $errors[] = [
                "El tipo de venta \"{$row['tipo_venta']}\" no existe en el sistema."
                    .$this->suggestion($row['tipo_venta'], $this->saleTypeNames),
                'Tipo de venta no encontrado en el sistema',
            ];
        }

        if (
            ! empty($row['cantidad_minima_super_mayoreo'])
            && ! empty($row['cantidad_minima_mayoreo'])
            && $row['cantidad_minima_super_mayoreo'] <= $row['cantidad_minima_mayoreo']
        ) {
            $errors[] = [
                "La Cantidad Mínima Super Mayoreo ({$row['cantidad_minima_super_mayoreo']}) debe ser mayor "
                    ."que la de mayoreo ({$row['cantidad_minima_mayoreo']})",
                'Cantidad Mínima Super Mayoreo menor o igual a la de mayoreo',
            ];
        }

        return $errors;
    }

    /**
     * Un catalogo mal escrito ("Abarrote" por "Abarrotes") es el error mas comun
     * del archivo: apuntar al nombre parecido ahorra buscarlo a mano.
     *
     * @param  array<string, string>  $names  Nombre normalizado => nombre original
     */
    private function suggestion(string $value, array $names): string
    {
        $needle = $this->normalize($value);
        $best = null;
        $bestScore = 0.0;

        foreach ($names as $normalized => $original) {
            similar_text($needle, $normalized, $percent);

            if ($percent > $bestScore) {
                $bestScore = $percent;
                $best = $original;
            }
        }

        return $bestScore >= 60 ? " ¿Quisiste decir \"{$best}\"?" : '';
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
