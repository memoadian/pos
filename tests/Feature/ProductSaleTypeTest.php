<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\CashRegister;
use App\Models\Department;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductBranchPrice;
use App\Models\SaleType;
use App\Models\User;
use App\Services\SaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Un producto se puede vender de varias formas (pza, kg, caja): cada tipo tiene
 * su propio precio y un factor de conversion hacia la unidad base, que es la
 * unidad del tipo principal y la que guarda el inventario.
 */
class ProductSaleTypeTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branch;
    private Department $department;
    private SaleType $pieza;
    private SaleType $caja;
    private SaleType $kilo;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->branch = Branch::create(['name' => 'Centro', 'address' => 'Calle 1', 'is_active' => true]);
        $this->department = Department::create(['name' => 'Abarrotes']);

        $this->pieza = SaleType::create(['name' => 'Pieza', 'base_unit' => 'pza', 'is_active' => true]);
        $this->caja = SaleType::create(['name' => 'Caja', 'base_unit' => 'caja', 'is_active' => true]);
        $this->kilo = SaleType::create(['name' => 'Kilogramos', 'base_unit' => 'kg', 'allows_decimals' => true, 'is_active' => true]);

        // Producto base: se vende por pza (principal) y por caja de 24
        $this->product = Product::create([
            'department_id' => $this->department->id,
            'barcode' => '111',
            'name' => 'Coca 600ml',
            'sale_type_id' => $this->pieza->id,
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

    private function addBoxSaleType(float $factor = 24, float $retail = 400.00): void
    {
        $this->product->productSaleTypes()->create([
            'sale_type_id' => $this->caja->id,
            'conversion_factor' => $factor,
            'price_retail' => $retail,
            'price_wholesale' => 380.00,
            'price_super_wholesale' => 360.00,
            'min_wholesale_qty' => 5,
            'min_super_wholesale_qty' => 10,
        ]);

        $this->product->unsetRelation('productSaleTypes');
    }

    private function admin(string $username): User
    {
        Role::findOrCreate('Admin');

        $user = User::factory()->create([
            'username' => $username,
            'branch_id' => $this->branch->id,
            'current_branch_id' => $this->branch->id,
        ]);
        $user->assignRole('Admin');

        return $user;
    }

    /**
     * El ProductObserver ya crea la fila de inventario de cada sucursal al dar
     * de alta el producto, aqui solo se le pone stock.
     */
    private function setStock(float $quantity): void
    {
        Inventory::updateOrCreate(
            ['product_id' => $this->product->id, 'branch_id' => $this->branch->id],
            ['stock_quantity' => $quantity]
        );
    }

    private function openCashRegister(User $user): CashRegister
    {
        return CashRegister::create([
            'branch_id' => $this->branch->id,
            'user_id' => $user->id,
            'opened_at' => now(),
            'opening_amount' => 100,
            'status' => 'abierta',
        ]);
    }

    public function test_producto_sin_tipos_extra_solo_ofrece_el_principal(): void
    {
        $options = $this->product->saleTypeOptions();

        $this->assertCount(1, $options);
        $this->assertSame($this->pieza->id, $options[0]['sale_type_id']);
        $this->assertTrue($options[0]['is_default']);
        $this->assertSame(1.0, $options[0]['conversion_factor']);
        $this->assertSame(20.00, $options[0]['price_retail']);
    }

    public function test_cada_tipo_de_venta_trae_su_precio_y_su_factor(): void
    {
        $this->addBoxSaleType();

        $options = $this->product->saleTypeOptions();

        $this->assertCount(2, $options);
        $this->assertTrue($options[0]['is_default'], 'el principal va primero');

        $box = $options[1];
        $this->assertSame($this->caja->id, $box['sale_type_id']);
        $this->assertSame(24.0, $box['conversion_factor']);
        $this->assertSame(400.00, $box['price_retail']);
        $this->assertSame(5, $box['min_wholesale_qty']);

        // El precio por cantidad usa los umbrales del tipo, no los del producto
        $this->assertSame(400.00, $this->product->getPriceForQuantity(1, null, $this->caja->id));
        $this->assertSame(380.00, $this->product->getPriceForQuantity(5, null, $this->caja->id));
        $this->assertSame(20.00, $this->product->getPriceForQuantity(1, null, $this->pieza->id));
    }

    public function test_el_override_de_sucursal_aplica_solo_a_su_tipo_de_venta(): void
    {
        $this->addBoxSaleType();

        ProductBranchPrice::create([
            'product_id' => $this->product->id,
            'branch_id' => $this->branch->id,
            'sale_type_id' => $this->caja->id,
            'price_retail' => 380.00,
        ]);
        $this->product->unsetRelation('branchPrices');

        $this->assertSame(380.00, $this->product->effectivePrices($this->branch->id, $this->caja->id)['price_retail']);
        $this->assertSame(20.00, $this->product->effectivePrices($this->branch->id, $this->pieza->id)['price_retail'], 'el tipo principal no se toca');
    }

    public function test_alta_guarda_los_tipos_marcados_y_el_principal(): void
    {
        $admin = $this->admin('admin_alta');

        $this->actingAs($admin)
            ->post(route('products.store'), [
                'department_id' => $this->department->id,
                'barcode' => '222',
                'name' => 'Sabritas',
                'sale_type_ids' => [$this->pieza->id, $this->caja->id],
                'default_sale_type_id' => $this->pieza->id,
                'price_retail' => 18.00,
                'price_wholesale' => 16.00,
                'price_super_wholesale' => 15.00,
                'cost' => 10.00,
                'sale_types' => [
                    $this->caja->id => [
                        'conversion_factor' => 12,
                        'price_retail' => 200.00,
                        'price_wholesale' => 190.00,
                        'price_super_wholesale' => 180.00,
                    ],
                ],
                'is_active' => 1,
            ])
            ->assertRedirect(route('products.index'));

        $product = Product::where('barcode', '222')->first();

        $this->assertSame($this->pieza->id, $product->sale_type_id);
        $this->assertSame('pza', $product->unit_base, 'la unidad base es la del tipo principal');
        $this->assertDatabaseHas('product_sale_types', [
            'product_id' => $product->id,
            'sale_type_id' => $this->caja->id,
            'conversion_factor' => 12,
            'price_retail' => 200.00,
        ]);
        $this->assertCount(2, $product->saleTypeOptions());
    }

    public function test_desmarcar_un_tipo_borra_su_fila_y_sus_precios_por_sucursal(): void
    {
        $this->addBoxSaleType();
        ProductBranchPrice::create([
            'product_id' => $this->product->id,
            'branch_id' => $this->branch->id,
            'sale_type_id' => $this->caja->id,
            'price_retail' => 380.00,
        ]);

        $admin = $this->admin('admin_baja');

        $this->actingAs($admin)
            ->put(route('products.update', $this->product), [
                'department_id' => $this->department->id,
                'barcode' => '111',
                'name' => 'Coca 600ml',
                'sale_type_ids' => [$this->pieza->id],
                'default_sale_type_id' => $this->pieza->id,
                'price_retail' => 20.00,
                'price_wholesale' => 18.00,
                'price_super_wholesale' => 15.00,
                'cost' => 12.00,
                'is_active' => 1,
            ])
            ->assertRedirect(route('products.index'));

        $this->assertDatabaseCount('product_sale_types', 0);
        $this->assertDatabaseCount('product_branch_prices', 0);
    }

    public function test_el_principal_debe_ser_uno_de_los_tipos_marcados(): void
    {
        $admin = $this->admin('admin_invalido');

        $this->actingAs($admin)
            ->from(route('products.create'))
            ->post(route('products.store'), [
                'department_id' => $this->department->id,
                'barcode' => '333',
                'name' => 'Producto malo',
                'sale_type_ids' => [$this->pieza->id],
                'default_sale_type_id' => $this->caja->id,
                'price_retail' => 10.00,
                'price_wholesale' => 9.00,
                'price_super_wholesale' => 8.00,
                'cost' => 5.00,
            ])
            ->assertSessionHasErrors('default_sale_type_id');

        $this->assertDatabaseMissing('products', ['barcode' => '333']);
    }

    public function test_los_formularios_muestran_los_tipos_como_checkboxes(): void
    {
        $this->addBoxSaleType();
        $admin = $this->admin('admin_form');

        $this->actingAs($admin)
            ->get(route('products.create'))
            ->assertOk()
            ->assertSee('sale_type_ids[]', false)
            ->assertSee('default_sale_type_id', false);

        // En edicion, el tipo extra llega marcado y con su panel de precios
        $this->actingAs($admin)
            ->get(route('products.edit', $this->product))
            ->assertOk()
            ->assertSee('sale_types[' . $this->caja->id . '][conversion_factor]', false)
            ->assertSee('prices[' . $this->branch->id . '][' . $this->caja->id . '][price_retail]', false);

        // La pantalla del POS tambien debe seguir renderizando
        $this->openCashRegister($admin);
        $this->actingAs($admin)
            ->get(route('pos.index'))
            ->assertOk()
            ->assertSee('setSaleType', false);
    }

    public function test_no_se_puede_borrar_un_tipo_usado_solo_como_adicional(): void
    {
        $this->addBoxSaleType();
        $admin = $this->admin('admin_borrado');

        $this->actingAs($admin)
            ->delete(route('sale-types.destroy', $this->caja))
            ->assertRedirect(route('sale-types.index'));

        $this->assertDatabaseHas('sale_types', ['id' => $this->caja->id]);

        // El listado cuenta los dos usos (principal y adicional)
        $this->actingAs($admin)
            ->get(route('sale-types.index'))
            ->assertOk()
            ->assertSee('1 productos');
    }

    public function test_el_pos_devuelve_los_tipos_de_venta_del_producto(): void
    {
        $this->addBoxSaleType();

        $admin = $this->admin('admin_pos');
        $this->openCashRegister($admin);

        $this->setStock(100);

        $response = $this->actingAs($admin)
            ->getJson(route('pos.products.search', ['query' => 'Coca']));

        $response->assertOk();
        $saleTypes = $response->json('products.0.sale_types');

        $this->assertCount(2, $saleTypes);
        $this->assertTrue($saleTypes[0]['is_default']);
        $this->assertEquals($this->caja->id, $saleTypes[1]['sale_type_id']);
        $this->assertEquals(24, $saleTypes[1]['conversion_factor']);
        $this->assertEquals(400.00, $saleTypes[1]['price_retail']);
    }

    public function test_vender_con_un_tipo_extra_descuenta_el_stock_convertido(): void
    {
        $this->addBoxSaleType();

        $admin = $this->admin('admin_venta');
        $this->actingAs($admin);
        $cashRegister = $this->openCashRegister($admin);

        $this->setStock(100);

        $sale = app(SaleService::class)->processSale([
            ['product_id' => $this->product->id, 'sale_type_id' => $this->caja->id, 'quantity' => 2, 'unit_price' => 400.00],
        ], $cashRegister);

        // 2 cajas de 24 = 48 piezas descontadas del inventario
        $this->assertEquals(52, Inventory::where('product_id', $this->product->id)->value('stock_quantity'));

        $item = $sale->items->first();
        $this->assertEquals($this->caja->id, $item->sale_type_id);
        $this->assertEquals(24, $item->conversion_factor);
        $this->assertEquals(2, $item->quantity);
        // El costo de una caja es el del producto por su factor: 12 * 24
        $this->assertEquals(288.00, $item->cost);
        $this->assertEquals(800.00, $sale->total);
        $this->assertEquals(800.00 - 576.00, $sale->profit);

        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $this->product->id,
            'type' => 'OUT',
            'quantity' => 48,
        ]);
    }

    public function test_dos_tipos_del_mismo_producto_comparten_el_stock(): void
    {
        $this->addBoxSaleType();

        $admin = $this->admin('admin_mixto');
        $this->actingAs($admin);
        $cashRegister = $this->openCashRegister($admin);

        $this->setStock(30);

        $items = [
            ['product_id' => $this->product->id, 'sale_type_id' => $this->caja->id, 'quantity' => 1, 'unit_price' => 400.00],
            ['product_id' => $this->product->id, 'sale_type_id' => $this->pieza->id, 'quantity' => 6, 'unit_price' => 20.00],
        ];

        // 24 + 6 = 30 piezas: entra justo
        app(SaleService::class)->processSale($items, $cashRegister);
        $this->assertEquals(0, Inventory::where('product_id', $this->product->id)->value('stock_quantity'));

        // Una pieza mas ya no cabe y la venta completa se rechaza
        Inventory::where('product_id', $this->product->id)->update(['stock_quantity' => 29]);

        $this->expectException(\Exception::class);
        app(SaleService::class)->processSale($items, $cashRegister);
    }

    public function test_no_se_puede_vender_con_un_tipo_ajeno_al_producto(): void
    {
        $admin = $this->admin('admin_ajeno');
        $this->actingAs($admin);
        $cashRegister = $this->openCashRegister($admin);

        $this->setStock(10);

        $this->expectExceptionMessage('El tipo de venta seleccionado no aplica');

        app(SaleService::class)->processSale([
            ['product_id' => $this->product->id, 'sale_type_id' => $this->kilo->id, 'quantity' => 1, 'unit_price' => 20.00],
        ], $cashRegister);
    }

    public function test_el_checkout_valida_el_stock_en_unidad_base(): void
    {
        $this->addBoxSaleType();

        $admin = $this->admin('admin_checkout');
        $this->openCashRegister($admin);

        $this->setStock(20);

        // 1 caja son 24 piezas y solo hay 20
        $this->actingAs($admin)
            ->postJson(route('pos.checkout'), [
                'items' => [
                    ['product_id' => $this->product->id, 'sale_type_id' => $this->caja->id, 'quantity' => 1, 'unit_price' => 400.00],
                ],
                'payment_method' => 'efectivo',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('items.0.quantity');
    }
}
