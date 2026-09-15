<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Product;
use App\Models\SaleType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProductMinimumTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Department $department;

    private SaleType $saleType;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'Admin']);
        $this->admin = User::factory()->create(['username' => 'admin_minimos']);
        $this->admin->assignRole('Admin');

        $this->department = Department::create(['name' => 'Abarrotes']);
        $this->saleType = SaleType::create(['name' => 'Pieza', 'base_unit' => 'pza', 'is_active' => true]);
    }

    public function test_la_pantalla_muestra_los_minimos_actuales(): void
    {
        $this->createProduct('Cloro', ['min_wholesale_qty' => 12, 'min_super_wholesale_qty' => 24]);

        $response = $this->actingAs($this->admin)->get(route('products.minimums.edit'));

        $response->assertOk();
        $response->assertSee('Cloro');
        $response->assertSee('value="12"', false);
        $response->assertSee('value="24"', false);
    }

    public function test_guarda_los_minimos_editados(): void
    {
        $product = $this->createProduct('Cloro');

        $this->actingAs($this->admin)
            ->put(route('products.minimums.update'), [
                'products' => [
                    $product->id => ['id' => $product->id, 'min_wholesale_qty' => 12, 'min_super_wholesale_qty' => 24],
                ],
            ])
            ->assertRedirect(route('products.minimums.edit'));

        $product->refresh();
        $this->assertSame(12, $product->min_wholesale_qty);
        $this->assertSame(24, $product->min_super_wholesale_qty);
    }

    public function test_super_mayoreo_debe_ser_mayor_que_mayoreo_y_no_guarda_nada_si_falla(): void
    {
        $cloro = $this->createProduct('Cloro', ['min_wholesale_qty' => 5, 'min_super_wholesale_qty' => 10]);
        $jabon = $this->createProduct('Jabon');

        $response = $this->actingAs($this->admin)
            ->put(route('products.minimums.update'), [
                'products' => [
                    $cloro->id => ['id' => $cloro->id, 'min_wholesale_qty' => 30, 'min_super_wholesale_qty' => 10],
                    $jabon->id => ['id' => $jabon->id, 'min_wholesale_qty' => 6, 'min_super_wholesale_qty' => null],
                ],
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');

        // Todo o nada: ni siquiera la fila valida (jabon) se guarda.
        $cloro->refresh();
        $jabon->refresh();
        $this->assertSame(5, $cloro->min_wholesale_qty);
        $this->assertSame(10, $cloro->min_super_wholesale_qty);
        $this->assertNull($jabon->min_wholesale_qty);
    }

    public function test_el_filtro_de_busqueda_acota_los_productos_mostrados(): void
    {
        $this->createProduct('Cloro');
        $this->createProduct('Jabon');

        $response = $this->actingAs($this->admin)
            ->get(route('products.minimums.edit', ['search' => 'Cloro']));

        $response->assertOk();
        $response->assertSee('Cloro');
        $response->assertDontSee('Jabon');
    }

    public function test_requiere_permiso_editar_productos(): void
    {
        $cashier = User::factory()->create(['username' => 'cajero_minimos']);

        $this->actingAs($cashier)->get(route('products.minimums.edit'))->assertForbidden();
        $this->actingAs($cashier)->put(route('products.minimums.update'), ['products' => []])->assertForbidden();
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function createProduct(string $name, array $extra = []): Product
    {
        return Product::create(array_merge([
            'department_id' => $this->department->id,
            'barcode' => (string) random_int(1000000, 9999999),
            'name' => $name,
            'sale_type_id' => $this->saleType->id,
            'unit_base' => 'pza',
            'price_retail' => 10.00,
            'price_wholesale' => 8.00,
            'price_super_wholesale' => 7.00,
            'cost' => 5.00,
            'is_active' => true,
        ], $extra));
    }
}
