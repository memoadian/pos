<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\CashRegister;
use App\Models\Department;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\SaleType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * El buscador del POS se dispara en cada tecleo del cajero: antes calculaba el
 * stock de cada resultado con una consulta aparte (y otra mas para el stock en
 * red), asi que 20 resultados costaban hasta 40 queries extra por letra.
 */
class PosSearchTest extends TestCase
{
    use RefreshDatabase;

    private Branch $centro;

    private Branch $norte;

    private Department $department;

    private SaleType $saleType;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'Admin']);

        $this->centro = Branch::create(['name' => 'Centro', 'address' => 'Calle 1', 'is_active' => true]);
        $this->norte = Branch::create(['name' => 'Norte', 'address' => 'Calle 2', 'is_active' => true]);

        $this->department = Department::create(['name' => 'Abarrotes']);
        $this->saleType = SaleType::create(['name' => 'Pieza', 'base_unit' => 'pza', 'is_active' => true]);

        $this->admin = User::factory()->create([
            'username' => 'admin_pos_search',
            'current_branch_id' => $this->centro->id,
        ]);
        $this->admin->assignRole('Admin');

        CashRegister::create([
            'branch_id' => $this->centro->id,
            'user_id' => $this->admin->id,
            'opened_at' => now(),
            'opening_amount' => 100,
            'status' => 'abierta',
        ]);

        $this->actingAs($this->admin);
    }

    public function test_el_stock_reportado_es_el_de_la_sucursal_activa(): void
    {
        $product = $this->createProduct('Producto 1');
        $this->setStock($product, $this->centro, 12);
        $this->setStock($product, $this->norte, 999);

        $response = $this->getJson(route('pos.products.search', ['query' => 'Producto']));

        $response->assertOk();
        $this->assertEquals(12.0, $response->json('products.0.stock'));
        // Sin "buscar en todas las sucursales", total_stock es el mismo que stock.
        $this->assertEquals(12.0, $response->json('products.0.total_stock'));
    }

    public function test_all_branches_suma_el_stock_de_todas_las_sucursales(): void
    {
        $product = $this->createProduct('Producto 1');
        $this->setStock($product, $this->centro, 12);
        $this->setStock($product, $this->norte, 8);

        $response = $this->getJson(route('pos.products.search', [
            'query' => 'Producto',
            'all_branches' => '1',
        ]));

        $this->assertEquals(12.0, $response->json('products.0.stock'));
        $this->assertEquals(20.0, $response->json('products.0.total_stock'));
    }

    public function test_un_producto_sin_inventario_reporta_stock_en_cero(): void
    {
        $product = $this->createProduct('Producto 1');
        // Sin setStock: la fila de inventario existe (la crea el observer) en cero.
        Inventory::where('product_id', $product->id)->where('branch_id', $this->centro->id)
            ->update(['stock_quantity' => 0]);

        $response = $this->getJson(route('pos.products.search', ['query' => 'Producto']));

        $this->assertEquals(0.0, $response->json('products.0.stock'));
        $this->assertSame('out_of_stock', $response->json('products.0.stock_status'));
    }

    public function test_buscar_no_dispara_una_consulta_de_stock_por_producto(): void
    {
        // Corrida de calentamiento: deja fuera de la medicion las queries de
        // rol/permisos y caja abierta, que solo se pagan la primera vez.
        $this->crearProductosConStock(1);
        $this->buscarYContar();

        $conPocos = $this->crearProductosConStockYContar(3);
        $conMuchos = $this->crearProductosConStockYContar(20);

        // El stock de todo el resultado se trae en un par de consultas agrupadas
        // (whereIn + pluck), no una por producto: el costo no depende de cuantos
        // productos haya en el resultado.
        $this->assertSame(
            $conPocos,
            $conMuchos,
            "Con pocos productos: {$conPocos} consultas; con muchos: {$conMuchos}",
        );
    }

    public function test_buscar_en_todas_las_sucursales_tampoco_crece_con_los_productos(): void
    {
        $this->crearProductosConStock(1);
        $this->buscarYContar(allBranches: true);

        $conPocos = $this->crearProductosConStockYContar(3, allBranches: true);
        $conMuchos = $this->crearProductosConStockYContar(20, allBranches: true);

        $this->assertSame(
            $conPocos,
            $conMuchos,
            "Con pocos productos: {$conPocos} consultas; con muchos: {$conMuchos}",
        );
    }

    private function crearProductosConStockYContar(int $cuantos, bool $allBranches = false): int
    {
        $this->crearProductosConStock($cuantos);

        return $this->buscarYContar($allBranches);
    }

    private function crearProductosConStock(int $cuantos): void
    {
        $desde = Product::count() + 1;

        foreach (range($desde, $desde + $cuantos - 1) as $i) {
            $product = $this->createProduct('Producto '.$i, (string) (1000 + $i));
            $this->setStock($product, $this->centro, 5);
            $this->setStock($product, $this->norte, 3);
        }
    }

    private function buscarYContar(bool $allBranches = false): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->getJson(route('pos.products.search', [
            'query' => 'Producto',
            'all_branches' => $allBranches ? '1' : '0',
        ]))->assertOk();

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        return count($queries);
    }

    private function createProduct(string $name, string $barcode = '111'): Product
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
}
