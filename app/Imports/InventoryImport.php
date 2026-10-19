<?php

namespace App\Imports;

use App\Models\Branch;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\Product;
use Illuminate\Support\Collection as BaseCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Throwable;

class InventoryImport implements ToCollection, WithHeadingRow, WithMultipleSheets
{
    public int $updated = 0;

    public int $unchanged = 0;

    public int $errorRows = 0;

    /** @var array<int, array{row: int, errors: array<int, string>}> */
    public array $errors = [];

    /** @var array<string, int> Motivo del error => cuantas filas lo tuvieron */
    public array $errorSummary = [];

    /** @var array<string, Product|null> */
    private array $productsByBarcode = [];

    /** @var array<string, int> Código => primera fila donde apareció */
    private array $seenBarcodes = [];

    private const MAX_REPORTED_ROWS = 500;

    /** @var array<string, string> Encabezado normalizado => etiqueta de la plantilla */
    private const COLUMN_LABELS = [
        'codigo_barras' => 'Código Barras',
        'producto' => 'Producto',
        'departamento' => 'Departamento',
        'sucursal' => 'Sucursal',
        'unidad' => 'Unidad',
        'stock_actual' => 'Stock Actual',
    ];

    private const REQUIRED_COLUMNS = ['codigo_barras', 'sucursal', 'stock_actual'];

    public function __construct(private Branch $branch) {}

    /**
     * Solo se procesa la primera hoja: las hojas auxiliares que Excel agregue
     * al archivo no deben interpretarse como inventario.
     *
     * @return array<int, self>
     */
    public function sheets(): array
    {
        return [0 => $this];
    }

    public function collection(BaseCollection $rows): void
    {
        if ($rows->isEmpty()) {
            return;
        }

        $missing = array_diff(self::REQUIRED_COLUMNS, array_keys($rows->first()->all()));

        if (! empty($missing)) {
            $labels = implode(', ', array_map(fn ($column) => self::COLUMN_LABELS[$column], $missing));

            $this->addError(1, [[
                "El archivo no coincide con la plantilla. Faltan las columnas: {$labels}. "
                    .'Descarga el inventario actual y vuelve a intentarlo.',
                'El archivo no coincide con la plantilla',
            ]]);

            return;
        }

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;
            $row = $row->map(fn ($value) => is_string($value) ? trim($value) : $value);

            if ($row->filter()->isEmpty()) {
                continue;
            }

            // Excel convierte códigos puramente numéricos en int/float.
            if ($row->get('codigo_barras') !== null) {
                $row->put('codigo_barras', trim((string) $row['codigo_barras']));
            }

            $errors = $this->validateRow($row);

            if (empty($errors)) {
                $product = $this->productFor($row['codigo_barras']);

                if (! $product) {
                    $errors[] = [
                        "El producto con código de barras \"{$row['codigo_barras']}\" no existe en el catálogo.",
                        'Producto no encontrado en el catálogo',
                    ];
                }
            }

            if (empty($errors) && ! $this->sameBranch($row['sucursal'])) {
                $errors[] = [
                    "La sucursal \"{$row['sucursal']}\" no es la sucursal activa ({$this->branch->name}). "
                        .'Cambia de sucursal en el encabezado o descarga el inventario de la sucursal correcta.',
                    'La sucursal del archivo no coincide con la sucursal activa',
                ];
            }

            if (empty($errors)) {
                $barcode = $row['codigo_barras'];

                if (isset($this->seenBarcodes[$barcode])) {
                    $errors[] = [
                        "El código de barras {$barcode} ya aparece en la fila {$this->seenBarcodes[$barcode]} del archivo.",
                        'Código de barras repetido en el archivo',
                    ];
                } else {
                    $this->seenBarcodes[$barcode] = $rowNumber;
                }
            }

            if (! empty($errors)) {
                $this->addError($rowNumber, $errors);

                continue;
            }

            try {
                $changed = $this->applyStock($product, (float) $row['stock_actual']);

                if ($changed) {
                    $this->updated++;
                } else {
                    $this->unchanged++;
                }
            } catch (Throwable $e) {
                $this->addError($rowNumber, [[
                    'No se pudo actualizar el inventario: '.$e->getMessage(),
                    'No se pudo actualizar el inventario',
                ]]);
            }
        }
    }

    /**
     * @return array<int, array{0: string, 1: string}> Pares [mensaje, motivo]
     */
    private function validateRow(BaseCollection $row): array
    {
        $validator = Validator::make($row->all(), [
            'codigo_barras' => ['required', 'string', 'max:255'],
            'sucursal' => ['required', 'string'],
            'stock_actual' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
        ], [
            'required' => 'Falta :attribute: la columna es obligatoria y vino vacía',
            'string' => ':attribute debe ser texto',
            'max' => ':attribute no puede pasar de :max (se recibió ":input")',
            'numeric' => ':attribute debe ser un número, sin letras ni símbolos (se recibió ":input")',
            'min' => ':attribute no puede ser negativo (se recibió ":input")',
        ], self::COLUMN_LABELS);

        if ($validator->fails()) {
            return array_map(
                fn ($message) => [$message, preg_replace('/ \(se recibió .*\)$/u', '', $message)],
                $validator->errors()->all(),
            );
        }

        return [];
    }

    private function productFor(string $barcode): ?Product
    {
        if (! array_key_exists($barcode, $this->productsByBarcode)) {
            $this->productsByBarcode[$barcode] = Product::where('barcode', $barcode)->first();
        }

        return $this->productsByBarcode[$barcode];
    }

    private function sameBranch(string $branchName): bool
    {
        return Str::lower(trim($branchName)) === Str::lower(trim($this->branch->name));
    }

    private function applyStock(Product $product, float $newStock): bool
    {
        return DB::transaction(function () use ($product, $newStock) {
            $inventory = Inventory::firstOrCreate(
                [
                    'product_id' => $product->id,
                    'branch_id' => $this->branch->id,
                ],
                ['stock_quantity' => 0],
            );

            $oldStock = (float) $inventory->stock_quantity;
            $newStock = round($newStock, 2);

            if (abs($oldStock - $newStock) < 0.005) {
                return false;
            }

            $inventory->update(['stock_quantity' => $newStock]);

            InventoryMovement::create([
                'product_id' => $product->id,
                'branch_id' => $this->branch->id,
                'user_id' => auth()->id(),
                'type' => 'ADJUST',
                'quantity' => $newStock,
                'reason' => 'Importación de inventario desde Excel. Stock anterior: '.number_format($oldStock, 2, '.', ''),
            ]);

            return true;
        });
    }

    /**
     * @param  array<int, array{0: string, 1: string}>  $errors
     */
    private function addError(int $rowNumber, array $errors): void
    {
        $messages = [];

        foreach ($errors as [$message, $reason]) {
            $messages[] = $message;
            $this->errorSummary[$reason] = ($this->errorSummary[$reason] ?? 0) + 1;
        }

        $this->errorRows++;

        if (count($this->errors) < self::MAX_REPORTED_ROWS) {
            $this->errors[] = ['row' => $rowNumber, 'errors' => $messages];
        }
    }
}
