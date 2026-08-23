<?php

namespace Tests\Feature;

use App\Http\Controllers\ProductController;
use App\Models\Department;
use App\Models\Product;
use App\Models\SaleType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProductListingTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Department $department;

    private SaleType $saleType;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'Admin']);
        $this->admin = User::factory()->create(['username' => 'admin_listado']);
        $this->admin->assignRole('Admin');

        $this->department = Department::create(['name' => 'Abarrotes']);
        $this->saleType = SaleType::create(['name' => 'Pieza', 'base_unit' => 'pza', 'is_active' => true]);
    }

    public function test_por_defecto_muestra_50_productos_por_pagina(): void
    {
        $this->createProducts(60);

        $response = $this->actingAs($this->admin)->get(route('products.index'));

        $response->assertOk();
        $this->assertSame(50, $response->viewData('products')->count());
        $this->assertSame(50, $response->viewData('perPage'));
    }

    public function test_el_selector_cambia_el_tamano_de_pagina(): void
    {
        $this->createProducts(60);

        foreach (ProductController::PER_PAGE_OPTIONS as $option) {
            $response = $this->actingAs($this->admin)
                ->get(route('products.index', ['per_page' => $option]));

            $this->assertSame(min($option, 60), $response->viewData('products')->count());
            $this->assertSame($option, $response->viewData('perPage'));
        }
    }

    public function test_un_per_page_fuera_de_la_lista_blanca_cae_al_default(): void
    {
        $this->createProducts(60);

        // Sin lista blanca, un per_page arbitrario traeria el catalogo completo.
        foreach (['999999', '0', '-1', 'todos', '15'] as $invalid) {
            $response = $this->actingAs($this->admin)
                ->get(route('products.index', ['per_page' => $invalid]));

            $this->assertSame(50, $response->viewData('products')->count(), "per_page={$invalid}");
        }
    }

    public function test_los_links_de_paginado_conservan_filtros_y_per_page(): void
    {
        $this->createProducts(30);

        $response = $this->actingAs($this->admin)->get(route('products.index', [
            'per_page' => 20,
            'search' => 'Producto',
            'department' => $this->department->id,
            'is_active' => '1',
        ]));

        $nextPage = $response->viewData('products')->nextPageUrl();

        $this->assertStringContainsString('per_page=20', $nextPage);
        $this->assertStringContainsString('search=Producto', $nextPage);
        $this->assertStringContainsString('department='.$this->department->id, $nextPage);
        $this->assertStringContainsString('is_active=1', $nextPage);
    }

    public function test_la_peticion_ajax_devuelve_filas_y_paginado(): void
    {
        $this->createProducts(30);

        $response = $this->actingAs($this->admin)
            ->getJson(route('products.index', ['per_page' => 20]), ['X-Requested-With' => 'XMLHttpRequest']);

        $response->assertOk()->assertJsonStructure(['rows', 'pagination']);

        $this->assertStringContainsString('Producto 001', $response->json('rows'));
        $this->assertStringContainsString('per_page=20', $response->json('pagination'));
    }

    private function createProducts(int $count): void
    {
        foreach (range(1, $count) as $i) {
            Product::create([
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
    }
}
