<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\CashRegister;
use App\Models\Department;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * El POS offline (ver resources/js/pos/*.js) cobra sin conexion y reenvia la
 * venta al servidor al reconectar con offline=true: una venta que ya ocurrio
 * fisicamente nunca debe rechazarse por falta de stock, solo marcarse
 * (stock_issue) para que un admin la revise. Estas pruebas cubren esa
 * excepcion y confirman que el checkout normal (online) sigue rechazando
 * el sobreventa como antes.
 */
class PosOfflineCheckoutTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branch;

    private Department $department;

    private SaleType $saleType;

    private User $cashier;

    private CashRegister $cashRegister;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'Admin']);

        $this->branch = Branch::create(['name' => 'Centro', 'address' => 'Calle 1', 'is_active' => true]);
        $this->department = Department::create(['name' => 'Abarrotes']);
        $this->saleType = SaleType::create(['name' => 'Pieza', 'base_unit' => 'pza', 'is_active' => true]);

        $this->cashier = User::factory()->create([
            'username' => 'cajero_offline',
            'current_branch_id' => $this->branch->id,
        ]);
        $this->cashier->assignRole('Admin');

        $this->cashRegister = CashRegister::create([
            'branch_id' => $this->branch->id,
            'user_id' => $this->cashier->id,
            'opened_at' => now(),
            'opening_amount' => 100,
            'status' => 'abierta',
        ]);

        $this->actingAs($this->cashier);
    }

    private function createProduct(string $name = 'Producto offline'): Product
    {
        return Product::create([
            'department_id' => $this->department->id,
            'barcode' => '999',
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

    public function test_una_venta_offline_se_acepta_aunque_no_haya_stock_suficiente(): void
    {
        $product = $this->createProduct();
        Inventory::query()->updateOrCreate(
            ['product_id' => $product->id, 'branch_id' => $this->branch->id],
            ['stock_quantity' => 2],
        );

        $response = $this->postJson(route('pos.checkout'), [
            'items' => [
                ['product_id' => $product->id, 'sale_type_id' => $this->saleType->id, 'quantity' => 5, 'unit_price' => 10],
            ],
            'payment_method' => 'efectivo',
            'idempotency_key' => 'test-offline-key-1',
            'offline' => true,
            'sold_at' => '2026-09-20 10:00:00',
            'offline_ref' => 'OFF-TEST0001',
        ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);

        $sale = Sale::where('idempotency_key', 'test-offline-key-1')->firstOrFail();
        $this->assertTrue((bool) $sale->stock_issue);
        $this->assertSame('OFF-TEST0001', $sale->offline_ref);
        $this->assertNotNull($sale->sold_at);

        // El stock queda en negativo (se acepto la venta, no se recorta a
        // cero): asi el admin ve exactamente cuanto se sobrevendio.
        $this->assertEquals(-3.0, Inventory::where('product_id', $product->id)->first()->stock_quantity);
    }

    public function test_una_venta_offline_sin_registro_de_inventario_tambien_se_acepta(): void
    {
        $product = $this->createProduct('Sin inventario previo');
        // ProductObserver crea un Inventory en 0 para cada sucursal activa al
        // dar de alta el producto; se borra para simular el caso real (una
        // sucursal creada despues de este producto, sin ese registro).
        Inventory::where('product_id', $product->id)->where('branch_id', $this->branch->id)->delete();

        $response = $this->postJson(route('pos.checkout'), [
            'items' => [
                ['product_id' => $product->id, 'sale_type_id' => $this->saleType->id, 'quantity' => 1, 'unit_price' => 10],
            ],
            'payment_method' => 'efectivo',
            'idempotency_key' => 'test-offline-key-2',
            'offline' => true,
            'offline_ref' => 'OFF-TEST0002',
        ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);

        $sale = Sale::where('idempotency_key', 'test-offline-key-2')->firstOrFail();
        $this->assertTrue((bool) $sale->stock_issue);
        $this->assertEquals(-1.0, Inventory::where('product_id', $product->id)->first()->stock_quantity);
    }

    public function test_una_venta_normal_online_sigue_rechazando_stock_insuficiente(): void
    {
        $product = $this->createProduct();
        Inventory::query()->updateOrCreate(
            ['product_id' => $product->id, 'branch_id' => $this->branch->id],
            ['stock_quantity' => 2],
        );

        $response = $this->postJson(route('pos.checkout'), [
            'items' => [
                ['product_id' => $product->id, 'sale_type_id' => $this->saleType->id, 'quantity' => 5, 'unit_price' => 10],
            ],
            'payment_method' => 'efectivo',
            'idempotency_key' => 'test-online-key-1',
        ]);

        $response->assertStatus(422);
        $this->assertNull(Sale::where('idempotency_key', 'test-online-key-1')->first());
    }

    public function test_catalogo_offline_incluye_precios_y_stock_de_la_sucursal(): void
    {
        $product = $this->createProduct('Catalogo offline');
        Inventory::query()->updateOrCreate(
            ['product_id' => $product->id, 'branch_id' => $this->branch->id],
            ['stock_quantity' => 7],
        );

        $response = $this->getJson(route('pos.catalog'));

        $response->assertOk();
        $response->assertJsonPath('success', true);

        $item = collect($response->json('products'))->firstWhere('id', $product->id);
        $this->assertNotNull($item);
        $this->assertEquals(7.0, $item['stock']);
        $this->assertEquals(10.0, $item['price_retail']);
    }
}
