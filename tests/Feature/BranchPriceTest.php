<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\CashRegister;
use App\Models\Department;
use App\Models\Product;
use App\Models\ProductBranchPrice;
use App\Models\SaleType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BranchPriceTest extends TestCase
{
    use RefreshDatabase;

    private Branch $centro;
    private Branch $norte;
    private Product $product;
    private SaleType $saleType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->centro = Branch::create(['name' => 'Centro', 'address' => 'Calle 1', 'is_active' => true]);
        $this->norte = Branch::create(['name' => 'Norte', 'address' => 'Calle 2', 'is_active' => true]);

        $dept = Department::create(['name' => 'Abarrotes']);
        $this->saleType = SaleType::create(['name' => 'Pieza', 'base_unit' => 'pza', 'is_active' => true]);

        $this->product = Product::create([
            'department_id' => $dept->id,
            'barcode' => '111',
            'name' => 'Coca 600ml',
            'sale_type_id' => $this->saleType->id,
            'unit_base' => 'pza',
            'price_retail' => 20.00,
            'price_wholesale' => 18.00,
            'price_super_wholesale' => 15.00,
            'cost' => 12.00,
            'min_wholesale_qty' => 10,
            'min_super_wholesale_qty' => 50,
            'is_active' => true,
        ]);
    }

    public function test_sin_override_hereda_los_precios_base(): void
    {
        $prices = $this->product->effectivePrices($this->centro->id);

        $this->assertSame(20.00, $prices['price_retail']);
        $this->assertSame(18.00, $prices['price_wholesale']);
        $this->assertSame(15.00, $prices['price_super_wholesale']);
    }

    public function test_override_aplica_solo_a_su_sucursal(): void
    {
        ProductBranchPrice::create([
            'product_id' => $this->product->id,
            'branch_id' => $this->norte->id,
            'sale_type_id' => $this->saleType->id,
            'price_retail' => 25.00,
            'price_wholesale' => 22.00,
            'price_super_wholesale' => 19.00,
        ]);
        $this->product->load('branchPrices');

        $this->assertSame(25.00, $this->product->effectivePrices($this->norte->id)['price_retail']);
        $this->assertSame(20.00, $this->product->effectivePrices($this->centro->id)['price_retail']);
    }

    public function test_override_parcial_hereda_los_niveles_no_definidos(): void
    {
        ProductBranchPrice::create([
            'product_id' => $this->product->id,
            'branch_id' => $this->norte->id,
            'sale_type_id' => $this->saleType->id,
            'price_retail' => 25.00,
            'price_wholesale' => null,
            'price_super_wholesale' => null,
        ]);
        $this->product->load('branchPrices');

        $prices = $this->product->effectivePrices($this->norte->id);

        $this->assertSame(25.00, $prices['price_retail'], 'el nivel sobrescrito debe usar el override');
        $this->assertSame(18.00, $prices['price_wholesale'], 'el nivel sin override hereda el base');
        $this->assertSame(15.00, $prices['price_super_wholesale'], 'el nivel sin override hereda el base');
    }

    public function test_precio_por_cantidad_respeta_la_sucursal(): void
    {
        ProductBranchPrice::create([
            'product_id' => $this->product->id,
            'branch_id' => $this->norte->id,
            'sale_type_id' => $this->saleType->id,
            'price_retail' => 25.00,
            'price_wholesale' => 22.00,
            'price_super_wholesale' => 19.00,
        ]);
        $this->product->load('branchPrices');

        // 1 pza -> menudeo, 10 -> mayoreo, 50 -> super mayoreo
        $this->assertSame(25.00, $this->product->getPriceForQuantity(1, $this->norte->id));
        $this->assertSame(22.00, $this->product->getPriceForQuantity(10, $this->norte->id));
        $this->assertSame(19.00, $this->product->getPriceForQuantity(50, $this->norte->id));

        $this->assertSame(20.00, $this->product->getPriceForQuantity(1, $this->centro->id));
        $this->assertSame(18.00, $this->product->getPriceForQuantity(10, $this->centro->id));
        $this->assertSame(15.00, $this->product->getPriceForQuantity(50, $this->centro->id));
    }

    public function test_sin_sucursal_usa_los_precios_base(): void
    {
        ProductBranchPrice::create([
            'product_id' => $this->product->id,
            'branch_id' => $this->norte->id,
            'sale_type_id' => $this->saleType->id,
            'price_retail' => 25.00,
        ]);
        $this->product->load('branchPrices');

        $this->assertSame(20.00, $this->product->effectivePrices(null)['price_retail']);
    }

    public function test_sucursal_nueva_hereda_sin_backfill(): void
    {
        // El caso que motiva el diseño: alta de sucursal despues del producto
        $sur = Branch::create(['name' => 'Sur', 'address' => 'Calle 3', 'is_active' => true]);

        $this->assertSame(20.00, $this->product->effectivePrices($sur->id)['price_retail']);
        $this->assertDatabaseCount('product_branch_prices', 0);
    }

    public function test_sync_guarda_borra_y_deja_heredar(): void
    {
        Role::create(['name' => 'Admin']);
        $admin = User::factory()->create([
            'username' => 'admin_sync',
            'branch_id' => $this->centro->id,
        ]);
        $admin->assignRole('Admin');

        // Guarda un override para Norte, deja Centro heredando
        $this->actingAs($admin)
            ->put(route('products.branch-prices.sync', $this->product), [
                'prices' => [
                    $this->centro->id => [
                        $this->saleType->id => ['price_retail' => '', 'price_wholesale' => '', 'price_super_wholesale' => ''],
                    ],
                    $this->norte->id => [
                        $this->saleType->id => ['price_retail' => '25.00', 'price_wholesale' => '', 'price_super_wholesale' => ''],
                    ],
                ],
            ])
            ->assertRedirect(route('products.edit', $this->product));

        $this->assertDatabaseCount('product_branch_prices', 1);
        $this->assertDatabaseHas('product_branch_prices', [
            'product_id' => $this->product->id,
            'branch_id' => $this->norte->id,
            'sale_type_id' => $this->saleType->id,
            'price_retail' => 25.00,
            'price_wholesale' => null,
        ]);

        // Limpiar los tres campos borra la fila (vuelve a heredar)
        $this->actingAs($admin)
            ->put(route('products.branch-prices.sync', $this->product), [
                'prices' => [
                    $this->norte->id => [
                        $this->saleType->id => ['price_retail' => '', 'price_wholesale' => '', 'price_super_wholesale' => ''],
                    ],
                ],
            ]);

        $this->assertDatabaseCount('product_branch_prices', 0);
    }

    public function test_pos_devuelve_el_precio_de_la_sucursal_activa(): void
    {
        Role::create(['name' => 'Admin']);
        $admin = User::factory()->create([
            'username' => 'admin_pos',
            'branch_id' => $this->norte->id,
            'current_branch_id' => $this->norte->id,
        ]);
        $admin->assignRole('Admin');

        // El POS exige caja abierta en la sucursal en curso
        CashRegister::create([
            'branch_id' => $this->norte->id,
            'user_id' => $admin->id,
            'opened_at' => now(),
            'opening_amount' => 100,
            'status' => 'abierta',
        ]);

        ProductBranchPrice::create([
            'product_id' => $this->product->id,
            'branch_id' => $this->norte->id,
            'sale_type_id' => $this->saleType->id,
            'price_retail' => 25.00,
        ]);

        $response = $this->actingAs($admin)
            ->getJson(route('pos.products.search', ['query' => 'Coca']));

        $response->assertOk();
        $product = $response->json('products.0');

        // assertEquals y no assertSame: JSON serializa 25.0 como 25
        $this->assertEquals(25.0, $product['price_retail'], 'menudeo debe venir del override de Norte');
        $this->assertEquals(18.0, $product['price_wholesale'], 'mayoreo sin override hereda el base');
    }
}
