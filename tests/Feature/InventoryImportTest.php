<?php

namespace Tests\Feature;

use App\Exports\InventoryExport;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\SaleType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InventoryImportTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Branch $centro;

    private Branch $norte;

    private Department $department;

    private SaleType $saleType;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'Admin']);
        Role::create(['name' => 'Vendedor']);

        $this->centro = Branch::create(['name' => 'Centro', 'address' => 'Calle 1', 'is_active' => true]);
        $this->norte = Branch::create(['name' => 'Norte', 'address' => 'Calle 2', 'is_active' => true]);

        $this->admin = User::factory()->create([
            'username' => 'admin_inventario_import',
            'current_branch_id' => $this->centro->id,
        ]);
        $this->admin->assignRole('Admin');

        $this->department = Department::create(['name' => 'Abarrotes']);
        $this->saleType = SaleType::create(['name' => 'Pieza', 'base_unit' => 'pza', 'is_active' => true]);
    }

    public function test_el_export_incluye_solo_la_sucursal_activa_y_respeta_los_filtros(): void
    {
        $conStock = $this->createProduct('111', 'Producto con stock');
        $sinStock = $this->createProduct('222', 'Producto sin stock');

        $this->setStock($conStock, $this->centro, 12);
        $this->setStock($sinStock, $this->centro, 0);
        $this->setStock($conStock, $this->norte, 7);

        Excel::fake();

        $this->actingAs($this->admin)
            ->get(route('inventory.export', ['in_stock' => '1']))
            ->assertOk();

        Excel::assertDownloaded(
            'inventario-centro-'.now()->format('Y-m-d').'.xlsx',
            function (InventoryExport $export) {
                $rows = $export->query()->get()->map(fn ($inventory) => $export->map($inventory))->all();

                $this->assertSame([
                    ['111', 'Producto con stock', 'Abarrotes', 'Centro', 'pza', 12.0],
                ], $rows);

                $this->assertSame(
                    ['Código Barras', 'Producto', 'Departamento', 'Sucursal', 'Unidad', 'Stock Actual'],
                    $export->headings(),
                );

                return true;
            },
        );
    }

    public function test_importa_stock_y_registra_un_movimiento_de_ajuste(): void
    {
        $product = $this->createProduct('111', 'Producto Uno');
        $this->setStock($product, $this->centro, 10);
        $this->setStock($product, $this->norte, 7);

        $response = $this->actingAs($this->admin)->post(route('inventory.import.store'), [
            'file' => $this->spreadsheetFile([
                ['111', 'Producto Uno', 'Abarrotes', 'Centro', 'pza', 20],
            ]),
        ]);

        $response->assertRedirect(route('inventory.index'));
        $response->assertSessionHas('success', 'Importación completa: 1 producto(s) actualizado(s), 0 sin cambios.');

        $this->assertSame(20.0, (float) Inventory::where([
            'product_id' => $product->id,
            'branch_id' => $this->centro->id,
        ])->value('stock_quantity'));

        // La sucursal no activa no se toca aunque el producto sea el mismo.
        $this->assertSame(7.0, (float) Inventory::where([
            'product_id' => $product->id,
            'branch_id' => $this->norte->id,
        ])->value('stock_quantity'));

        $movement = InventoryMovement::sole();
        $this->assertSame('ADJUST', $movement->type);
        $this->assertSame($this->centro->id, $movement->branch_id);
        $this->assertSame($this->admin->id, $movement->user_id);
        $this->assertSame(20.0, (float) $movement->quantity);
        $this->assertStringContainsString('Stock anterior: 10.00', $movement->reason);
    }

    public function test_el_formulario_de_importacion_usa_la_sucursal_activa(): void
    {
        $this->actingAs($this->admin)
            ->get(route('inventory.import.create'))
            ->assertOk()
            ->assertSee('Importar Inventario')
            ->assertSee('Actualiza el stock de Centro')
            ->assertSee('Descargar inventario');
    }

    public function test_reimportar_el_mismo_archivo_no_duplica_movimientos(): void
    {
        $product = $this->createProduct('111', 'Producto Uno');
        $this->setStock($product, $this->centro, 10);

        $file = $this->spreadsheetFile([
            ['111', 'Producto Uno', 'Abarrotes', 'Centro', 'pza', 20],
        ]);

        $this->actingAs($this->admin)
            ->post(route('inventory.import.store'), ['file' => $file]);

        $this->actingAs($this->admin)
            ->post(route('inventory.import.store'), ['file' => $file])
            ->assertSessionHas('success', 'Importación completa: el inventario ya coincidía con el archivo.');

        $this->assertSame(1, InventoryMovement::count());
    }

    public function test_el_excel_descargado_se_puede_editar_y_volver_a_importar(): void
    {
        $product = $this->createProduct('111', 'Producto Uno');
        $this->setStock($product, $this->centro, 10);

        $download = $this->actingAs($this->admin)->get(route('inventory.export'));
        $download->assertOk();

        // Se abre el archivo real generado por la descarga y se cambia el stock,
        // igual que lo haria el usuario en Excel.
        $spreadsheet = IOFactory::load($download->baseResponse->getFile()->getPathname());
        $spreadsheet->getActiveSheet()->setCellValue('F2', 25);

        $editedPath = tempnam(sys_get_temp_dir(), 'inventario_roundtrip').'.xlsx';
        (new Xlsx($spreadsheet))->save($editedPath);
        $editedFile = new UploadedFile(
            $editedPath,
            'inventario-editado.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true,
        );

        $this->actingAs($this->admin)
            ->post(route('inventory.import.store'), ['file' => $editedFile])
            ->assertRedirect(route('inventory.index'))
            ->assertSessionHas('success', 'Importación completa: 1 producto(s) actualizado(s), 0 sin cambios.');

        $this->assertSame(25.0, (float) Inventory::where([
            'product_id' => $product->id,
            'branch_id' => $this->centro->id,
        ])->value('stock_quantity'));
    }

    public function test_una_fila_de_otra_sucursal_se_omite_y_se_reporta(): void
    {
        $product = $this->createProduct('111', 'Producto Uno');
        $this->setStock($product, $this->centro, 10);
        $this->setStock($product, $this->norte, 7);

        $response = $this->actingAs($this->admin)->post(route('inventory.import.store'), [
            'file' => $this->spreadsheetFile([
                ['111', 'Producto Uno', 'Abarrotes', 'Norte', 'pza', 99],
            ]),
        ]);

        $response->assertRedirect(route('inventory.import.create'));
        $response->assertSessionHas('error');
        $this->assertSame(
            ['La sucursal del archivo no coincide con la sucursal activa' => 1],
            session('importErrorSummary'),
        );

        $this->assertSame(10.0, (float) Inventory::where([
            'product_id' => $product->id,
            'branch_id' => $this->centro->id,
        ])->value('stock_quantity'));
        $this->assertSame(7.0, (float) Inventory::where([
            'product_id' => $product->id,
            'branch_id' => $this->norte->id,
        ])->value('stock_quantity'));
        $this->assertSame(0, InventoryMovement::count());
    }

    public function test_un_codigo_desconocido_se_omite_sin_crear_productos(): void
    {
        $product = $this->createProduct('111', 'Producto Uno');
        $this->setStock($product, $this->centro, 10);

        $response = $this->actingAs($this->admin)->post(route('inventory.import.store'), [
            'file' => $this->spreadsheetFile([
                ['111', 'Producto Uno', 'Abarrotes', 'Centro', 'pza', 15],
                ['999', 'No existe', 'Abarrotes', 'Centro', 'pza', 8],
            ]),
        ]);

        $response->assertRedirect(route('inventory.import.create'));
        $response->assertSessionHas('success');
        $this->assertSame(
            ['Producto no encontrado en el catálogo' => 1],
            session('importErrorSummary'),
        );

        $this->assertSame(1, Product::count());
        $this->assertSame(15.0, (float) Inventory::where([
            'product_id' => $product->id,
            'branch_id' => $this->centro->id,
        ])->value('stock_quantity'));
    }

    public function test_un_stock_negativo_se_rechaza(): void
    {
        $product = $this->createProduct('111', 'Producto Uno');
        $this->setStock($product, $this->centro, 10);

        $this->actingAs($this->admin)->post(route('inventory.import.store'), [
            'file' => $this->spreadsheetFile([
                ['111', 'Producto Uno', 'Abarrotes', 'Centro', 'pza', -1],
            ]),
        ])->assertRedirect(route('inventory.import.create'));

        $this->assertSame(10.0, (float) Inventory::where([
            'product_id' => $product->id,
            'branch_id' => $this->centro->id,
        ])->value('stock_quantity'));
        $this->assertSame(0, InventoryMovement::count());
    }

    public function test_un_codigo_repetido_dentro_del_archivo_se_reporta(): void
    {
        $product = $this->createProduct('111', 'Producto Uno');
        $this->setStock($product, $this->centro, 10);

        $this->actingAs($this->admin)->post(route('inventory.import.store'), [
            'file' => $this->spreadsheetFile([
                ['111', 'Producto Uno', 'Abarrotes', 'Centro', 'pza', 15],
                ['111', 'Producto Uno', 'Abarrotes', 'Centro', 'pza', 20],
            ]),
        ])->assertRedirect(route('inventory.import.create'));

        $this->assertSame(
            ['Código de barras repetido en el archivo' => 1],
            session('importErrorSummary'),
        );
        // La primera fila sí se aplica; la repetida se omite para que el
        // resultado no dependa del orden accidental de las filas.
        $this->assertSame(15.0, (float) Inventory::where([
            'product_id' => $product->id,
            'branch_id' => $this->centro->id,
        ])->value('stock_quantity'));
        $this->assertSame(1, InventoryMovement::count());
    }

    public function test_ver_inventario_permite_exportar_pero_no_importar(): void
    {
        Permission::create(['name' => 'ver inventario']);
        $viewer = User::factory()->create([
            'username' => 'consulta_inventario',
            'branch_id' => $this->centro->id,
            'current_branch_id' => $this->centro->id,
        ]);
        $viewer->assignRole('Vendedor');
        $viewer->givePermissionTo('ver inventario');

        Excel::fake();
        $this->actingAs($viewer)->get(route('inventory.export'))->assertOk();
        $this->actingAs($viewer)->get(route('inventory.import.create'))->assertForbidden();
        $this->actingAs($viewer)->post(route('inventory.import.store'), [
            'file' => $this->spreadsheetFile([['111', 'Producto Uno', 'Abarrotes', 'Centro', 'pza', 20]]),
        ])->assertForbidden();
    }

    private function createProduct(string $barcode, string $name): Product
    {
        return Product::create([
            'department_id' => $this->department->id,
            'barcode' => $barcode,
            'name' => $name,
            'sale_type_id' => $this->saleType->id,
            'unit_base' => 'pza',
            'price_retail' => 10.00,
            'price_wholesale' => 8.00,
            'price_super_wholesale' => 0,
            'cost' => 5.00,
            'is_active' => true,
        ]);
    }

    private function setStock(Product $product, Branch $branch, float $quantity): void
    {
        Inventory::updateOrCreate(
            ['product_id' => $product->id, 'branch_id' => $branch->id],
            ['stock_quantity' => $quantity],
        );
    }

    /**
     * @param  array<int, array<int, mixed>>  $rows
     */
    private function spreadsheetFile(array $rows): UploadedFile
    {
        $headers = ['Código Barras', 'Producto', 'Departamento', 'Sucursal', 'Unidad', 'Stock Actual'];

        $sheet = new Spreadsheet;
        $worksheet = $sheet->getActiveSheet();
        $worksheet->fromArray($headers, null, 'A1', true);
        $worksheet->fromArray($rows, null, 'A2', true);

        $path = tempnam(sys_get_temp_dir(), 'inventario_import_test').'.xlsx';
        (new Xlsx($sheet))->save($path);

        return new UploadedFile($path, 'inventario.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }
}
