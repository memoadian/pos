<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Product;
use App\Models\SaleType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProductImportTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Department $department;

    private SaleType $saleType;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'Admin']);
        $this->admin = User::factory()->create(['username' => 'admin_import']);
        $this->admin->assignRole('Admin');

        $this->department = Department::create(['name' => 'Abarrotes']);
        $this->saleType = SaleType::create(['name' => 'Pieza', 'base_unit' => 'pza', 'is_active' => true]);
    }

    public function test_crea_productos_nuevos_y_actualiza_existentes_por_codigo_de_barras(): void
    {
        Product::create([
            'department_id' => $this->department->id,
            'barcode' => '111',
            'name' => 'Producto Viejo',
            'sale_type_id' => $this->saleType->id,
            'unit_base' => 'pza',
            'price_retail' => 10.00,
            'price_wholesale' => 8.00,
            'price_super_wholesale' => 6.00,
            'cost' => 5.00,
            'is_active' => true,
        ]);

        $file = $this->spreadsheetFile([
            ['111', 'Producto Actualizado', 'Abarrotes', 'Pieza', 5, 8, 7, 10, '', '', 3, 'Si'],
            ['222', 'Producto Nuevo', 'Abarrotes', 'Pieza', 2, 4, 3.5, '', 3, 50, '', ''],
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('products.import.store'), ['file' => $file]);

        $response->assertRedirect(route('products.index'));

        $this->assertDatabaseHas('products', [
            'barcode' => '111',
            'name' => 'Producto Actualizado',
            'price_super_wholesale' => 0,
            'min_super_wholesale_qty' => null,
        ]);

        $this->assertDatabaseHas('products', [
            'barcode' => '222',
            'name' => 'Producto Nuevo',
            'min_wholesale_qty' => null,
            'price_super_wholesale' => 3,
            'min_super_wholesale_qty' => 50,
        ]);
    }

    public function test_acepta_precios_con_formato_de_moneda(): void
    {
        $file = $this->spreadsheetFile([
            ['555', 'Producto Con Formato', 'Abarrotes', 'Pieza', '$5.00', '$8.00', '$7.00', '', '', '', '', ''],
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('products.import.store'), ['file' => $file]);

        $response->assertRedirect(route('products.index'));

        $this->assertDatabaseHas('products', [
            'barcode' => '555',
            'cost' => 5.00,
            'price_retail' => 8.00,
            'price_wholesale' => 7.00,
        ]);
    }

    public function test_precio_en_cero_ignora_la_cantidad_minima_del_nivel(): void
    {
        $file = $this->spreadsheetFile([
            ['666', 'Producto Sin Descuento', 'Abarrotes', 'Pieza', 1, 2, 0, 10, 0, 20, '', ''],
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('products.import.store'), ['file' => $file]);

        $response->assertRedirect(route('products.index'));

        $this->assertDatabaseHas('products', [
            'barcode' => '666',
            'price_wholesale' => 0,
            'min_wholesale_qty' => null,
            'price_super_wholesale' => 0,
            'min_super_wholesale_qty' => null,
        ]);
    }

    public function test_fila_con_departamento_inexistente_se_omite_y_se_reporta(): void
    {
        $file = $this->spreadsheetFile([
            ['333', 'Producto Malo', 'Departamento Fantasma', 'Pieza', 1, 2, 1.5, '', '', '', '', ''],
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('products.import.store'), ['file' => $file]);

        $response->assertSessionHas('importErrors');
        $this->assertDatabaseMissing('products', ['barcode' => '333']);
    }

    public function test_requiere_rol_admin(): void
    {
        $cashier = User::factory()->create(['username' => 'cajero_import']);

        $file = $this->spreadsheetFile([
            ['444', 'Producto', 'Abarrotes', 'Pieza', 1, 2, 1.5, '', '', '', '', ''],
        ]);

        $this->actingAs($cashier)
            ->post(route('products.import.store'), ['file' => $file])
            ->assertForbidden();
    }

    /**
     * @param  array<int, array<int, mixed>>  $rows
     */
    private function spreadsheetFile(array $rows): UploadedFile
    {
        $headers = ['Código Barras', 'Nombre', 'Departamento', 'Tipo Venta', 'Costo', 'Precio Menudeo', 'Precio Mayoreo', 'Cantidad Mínima Mayoreo', 'Precio Super Mayoreo', 'Cantidad Mínima Super Mayoreo', 'Stock Mínimo', 'Activo'];

        $sheet = new Spreadsheet;
        $worksheet = $sheet->getActiveSheet();
        // strictNullComparison=true: por defecto fromArray() usa "!=" contra null, y como
        // en PHP "0 == null" es true, cualquier celda con el valor 0 se omite al escribir.
        $worksheet->fromArray($headers, null, 'A1', true);
        $worksheet->fromArray($rows, null, 'A2', true);

        $path = tempnam(sys_get_temp_dir(), 'productos_import_test').'.xlsx';
        (new Xlsx($sheet))->save($path);

        return new UploadedFile($path, 'productos.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }
}
