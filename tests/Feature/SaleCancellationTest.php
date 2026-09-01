<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\CashRegister;
use App\Models\Department;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleType;
use App\Models\User;
use App\Services\SaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SaleCancellationTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branch;

    private User $admin;

    private User $vendedor;

    private CashRegister $cashRegister;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'Admin']);
        Role::create(['name' => 'Vendedor']);

        $this->branch = Branch::create(['name' => 'Centro', 'address' => 'Calle 1', 'is_active' => true]);

        $department = Department::create(['name' => 'Abarrotes']);
        $saleType = SaleType::create(['name' => 'Pieza', 'base_unit' => 'pza', 'is_active' => true]);

        $this->admin = User::factory()->create([
            'username' => 'admin_cancel',
            'current_branch_id' => $this->branch->id,
        ]);
        $this->admin->assignRole('Admin');

        $this->vendedor = User::factory()->create([
            'username' => 'vendedor_cancel',
            'branch_id' => $this->branch->id,
        ]);
        $this->vendedor->assignRole('Vendedor');

        $this->cashRegister = CashRegister::create([
            'branch_id' => $this->branch->id,
            'user_id' => $this->admin->id,
            'opened_at' => now(),
            'opening_amount' => 100,
            'status' => 'abierta',
        ]);

        $this->product = Product::create([
            'department_id' => $department->id,
            'barcode' => '1001',
            'name' => 'Refresco',
            'sale_type_id' => $saleType->id,
            'unit_base' => 'pza',
            'price_retail' => 20.00,
            'price_wholesale' => 18.00,
            'price_super_wholesale' => 0,
            'cost' => 12.00,
            'is_active' => true,
        ]);

        Inventory::updateOrCreate(
            ['product_id' => $this->product->id, 'branch_id' => $this->branch->id],
            ['stock_quantity' => 50],
        );

        $this->actingAs($this->admin);
    }

    private function crearVenta(string $paymentMethod = 'efectivo'): Sale
    {
        return app(SaleService::class)->processSale(
            items: [[
                'product_id' => $this->product->id,
                'quantity' => 3,
                'unit_price' => 20.00,
            ]],
            cashRegister: $this->cashRegister->fresh(),
            paymentMethod: $paymentMethod,
        );
    }

    public function test_cancelar_restaura_inventario_y_revierte_totales_de_caja(): void
    {
        $sale = $this->crearVenta();

        $this->assertEquals(47, Inventory::where('product_id', $this->product->id)->value('stock_quantity'));

        $this->actingAs($this->admin)
            ->post(route('sales.cancel', $sale), ['reason' => 'Cobro duplicado'])
            ->assertRedirect(route('sales.index'));

        $sale->refresh();
        $this->assertTrue($sale->isCancelled());
        $this->assertEquals('Cobro duplicado', $sale->cancellation_reason);
        $this->assertEquals($this->admin->id, $sale->cancelled_by);
        $this->assertNotNull($sale->cancelled_at);

        // Inventario devuelto
        $this->assertEquals(50, Inventory::where('product_id', $this->product->id)->value('stock_quantity'));
        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $this->product->id,
            'type' => 'IN',
            'quantity' => 3,
            'reason' => "CANCEL - Cancelación venta #{$sale->id}",
        ]);

        // Totales de caja revertidos
        $cr = $this->cashRegister->fresh();
        $this->assertEquals(0, $cr->total_sales);
        $this->assertEquals(0, $cr->cash_sales);
        $this->assertEquals(0, $cr->total_profit);
    }

    public function test_no_se_puede_cancelar_dos_veces(): void
    {
        $sale = $this->crearVenta();

        app(SaleService::class)->cancelSale($sale, null, $this->admin->id);

        $this->actingAs($this->admin)
            ->from(route('sales.show', $sale))
            ->post(route('sales.cancel', $sale))
            ->assertRedirect(route('sales.show', $sale))
            ->assertSessionHas('error');

        // El inventario no se devolvió por segunda vez
        $this->assertEquals(50, Inventory::where('product_id', $this->product->id)->value('stock_quantity'));
    }

    public function test_un_vendedor_no_puede_cancelar(): void
    {
        $sale = $this->crearVenta();

        $this->actingAs($this->vendedor)
            ->post(route('sales.cancel', $sale))
            ->assertForbidden();

        $this->assertFalse($sale->fresh()->isCancelled());
    }

    public function test_se_puede_cancelar_con_la_caja_cerrada(): void
    {
        $sale = $this->crearVenta();
        $this->cashRegister->update(['status' => 'cerrada', 'closed_at' => now(), 'closing_amount' => 160]);

        app(SaleService::class)->cancelSale($sale, null, $this->admin->id);

        $cr = $this->cashRegister->fresh();
        $this->assertEquals(0, $cr->total_sales);
        $this->assertEquals(0, $cr->cash_sales);
    }

    public function test_las_ventas_canceladas_no_cuentan_en_el_reporte(): void
    {
        $sale = $this->crearVenta();
        app(SaleService::class)->cancelSale($sale, null, $this->admin->id);

        $this->actingAs($this->admin)
            ->get(route('reports.index'))
            ->assertOk()
            ->assertViewHas('totalSalesCount', 0)
            ->assertViewHas('totalRevenue', 0.0);
    }
}
