<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Department;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\SaleType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InventoryListingTest extends TestCase
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

        $this->centro = Branch::create(['name' => 'Centro', 'address' => 'Calle 1', 'is_active' => true]);
        $this->norte = Branch::create(['name' => 'Norte', 'address' => 'Calle 2', 'is_active' => true]);

        $this->admin = User::factory()->create([
            'username' => 'admin_inventario',
            'current_branch_id' => $this->centro->id,
        ]);
        $this->admin->assignRole('Admin');

        $this->department = Department::create(['name' => 'Abarrotes']);
        $this->saleType = SaleType::create(['name' => 'Pieza', 'base_unit' => 'pza', 'is_active' => true]);
    }

    public function test_el_total_cuenta_solo_la_sucursal_activa(): void
    {
        // El observer da de alta la fila de inventario en las dos sucursales.
        $this->createProducts(4);

        $response = $this->actingAs($this->admin)->get(route('inventory.index'));

        $response->assertOk();
        $this->assertSame(8, Inventory::count());
        // La pantalla siempre habla de una sola sucursal: contar las dos mentiría.
        $this->assertSame(4, $response->viewData('totalInventories'));

        $this->assertStringContainsString(
            '1–4 de 4 productos',
            $this->summaryText($response->viewData('summary')),
        );
    }

    public function test_solo_con_stock_deja_fuera_las_filas_en_cero(): void
    {
        $products = $this->createProducts(5);
        $this->setStock($products[0], 12);
        $this->setStock($products[1], 3.5);

        $response = $this->actingAs($this->admin)
            ->get(route('inventory.index', ['in_stock' => '1']));

        $this->assertSame(2, $response->viewData('inventories')->total());

        $summary = $this->summaryText($response->viewData('summary'));
        $this->assertStringContainsString('1–2 de 2 productos', $summary);
        $this->assertStringContainsString('filtrado de 5', $summary);
    }

    public function test_sin_el_filtro_se_ven_todas_las_filas_incluidas_las_de_cero(): void
    {
        $products = $this->createProducts(5);
        $this->setStock($products[0], 12);

        $response = $this->actingAs($this->admin)->get(route('inventory.index'));

        $this->assertSame(5, $response->viewData('inventories')->total());
    }

    public function test_solo_con_stock_se_combina_con_stock_bajo(): void
    {
        $products = $this->createProducts(3);
        $products[0]->update(['min_stock' => 10]);
        $products[1]->update(['min_stock' => 10]);

        $this->setStock($products[0], 4);   // con stock y por debajo del mínimo
        $this->setStock($products[1], 50);  // con stock, arriba del mínimo
        // $products[2] queda en cero: lo saca "solo con stock" aunque no tenga mínimo

        $response = $this->actingAs($this->admin)
            ->get(route('inventory.index', ['in_stock' => '1', 'low_stock' => '1']));

        $inventories = $response->viewData('inventories');

        $this->assertSame(1, $inventories->total());
        $this->assertSame($products[0]->id, $inventories->first()->product_id);
    }

    public function test_el_ajax_devuelve_filas_paginado_y_resumen(): void
    {
        $this->createProducts(3);

        $response = $this->actingAs($this->admin)
            ->getJson(route('inventory.index'), ['X-Requested-With' => 'XMLHttpRequest']);

        // Antes devolvia solo el HTML de las filas: el resumen y el paginado se quedaban viejos.
        $response->assertOk()->assertJsonStructure(['rows', 'pagination', 'summary']);
        $this->assertStringContainsString('1–3', $this->summaryText($response->json('summary')));
    }

    public function test_los_links_de_paginado_conservan_el_filtro(): void
    {
        $products = $this->createProducts(40);
        foreach ($products as $product) {
            $this->setStock($product, 5);
        }

        $response = $this->actingAs($this->admin)
            ->get(route('inventory.index', ['in_stock' => '1']));

        $this->assertStringContainsString(
            'in_stock=1',
            $response->viewData('inventories')->nextPageUrl(),
        );
    }

    /** @return array<int, Product> */
    private function createProducts(int $count): array
    {
        $products = [];

        foreach (range(1, $count) as $i) {
            $products[] = Product::create([
                'department_id' => $this->department->id,
                'barcode' => (string) (1000 + $i),
                'name' => 'Producto '.str_pad((string) $i, 3, '0', STR_PAD_LEFT),
                'sale_type_id' => $this->saleType->id,
                'unit_base' => 'pza',
                'price_retail' => 10.00,
                'price_wholesale' => 8.00,
                'price_super_wholesale' => 0,
                'cost' => 5.00,
                'is_active' => true,
            ]);
        }

        return $products;
    }

    private function setStock(Product $product, float $quantity): void
    {
        Inventory::where('product_id', $product->id)
            ->where('branch_id', $this->centro->id)
            ->update(['stock_quantity' => $quantity]);
    }

    /** El resumen se afirma por su texto: el markup lleva saltos de linea del Blade. */
    private function summaryText(string $html): string
    {
        return trim(preg_replace('/\s+/u', ' ', strip_tags($html)));
    }
}
