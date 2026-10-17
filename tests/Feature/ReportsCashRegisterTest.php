<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\CashRegister;
use App\Models\Department;
use App\Models\Expense;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleType;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ReportsCashRegisterTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branch;

    private User $admin;

    private User $vendedor;

    private CashRegister $backdatedRegister;

    protected function setUp(): void
    {
        parent::setUp();

        // Fecha fija para distinguir claramente la fecha de la caja de la
        // fecha en que se capturan las ventas.
        Carbon::setTestNow('2026-10-17 12:00:00');

        Role::create(['name' => 'Admin']);
        Role::create(['name' => 'Vendedor']);

        $this->branch = Branch::create([
            'name' => 'Centro',
            'address' => 'Calle 1',
            'is_active' => true,
        ]);

        $this->admin = User::factory()->create([
            'username' => 'admin_reportes',
            'current_branch_id' => $this->branch->id,
        ]);
        $this->admin->assignRole('Admin');

        $this->vendedor = User::factory()->create([
            'username' => 'vendedor_reportes',
            'branch_id' => $this->branch->id,
        ]);
        $this->vendedor->assignRole('Vendedor');

        $this->backdatedRegister = CashRegister::create([
            'branch_id' => $this->branch->id,
            'user_id' => $this->vendedor->id,
            'opened_at' => Carbon::parse('2026-10-10 09:00:00'),
            'opening_amount' => 500,
            'status' => 'abierta',
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function createSale(
        float $total,
        string $status = 'completada',
        float $profit = 0,
        ?CashRegister $register = null
    ): Sale {
        return Sale::create([
            'branch_id' => $this->branch->id,
            'cash_register_id' => ($register ?? $this->backdatedRegister)->id,
            'user_id' => $this->vendedor->id,
            'subtotal' => $total,
            'total' => $total,
            'profit' => $profit,
            'payment_method' => 'efectivo',
            'status' => $status,
        ]);
    }

    public function test_el_reporte_por_caja_cuenta_las_ventas_en_la_fecha_de_apertura(): void
    {
        $this->createSale(100, profit: 20);
        $this->createSale(50, profit: 10);
        $this->createSale(999, status: 'cancelada');

        $this->actingAs($this->admin)
            ->get(route('reports.index', [
                'start_date' => '2026-10-10',
                'end_date' => '2026-10-10',
            ]))
            ->assertOk()
            ->assertSee('Reporte por caja diaria')
            ->assertSee('Cajas del período')
            ->assertViewHas('totalSalesCount', 2)
            ->assertViewHas('totalRevenue', 150.0)
            ->assertViewHas('totalProfit', 30.0)
            ->assertViewHas('totalRegisters', 1)
            ->assertViewHas('dailySummary', function ($dailySummary) {
                $day = $dailySummary->firstWhere('date', '2026-10-10');

                return $day !== null
                    && $day['registers'] === 1
                    && $day['count'] === 2
                    && $day['total'] === 150.0;
            });

        // El mismo día de captura no debe mostrar la venta si la caja es de
        // otra fecha.
        $this->actingAs($this->admin)
            ->get(route('reports.index', [
                'start_date' => '2026-10-17',
                'end_date' => '2026-10-17',
            ]))
            ->assertOk()
            ->assertViewHas('totalSalesCount', 0)
            ->assertViewHas('totalRegisters', 0);
    }

    public function test_el_modo_fecha_de_venta_conserva_el_filtro_por_captura(): void
    {
        $this->createSale(100);
        $this->createSale(50);

        $this->actingAs($this->admin)
            ->get(route('reports.index', [
                'group_by' => 'sale',
                'start_date' => '2026-10-17',
                'end_date' => '2026-10-17',
            ]))
            ->assertOk()
            ->assertViewHas('groupBy', 'sale')
            ->assertViewHas('totalSalesCount', 2)
            ->assertViewHas('totalRevenue', 150.0);
    }

    public function test_varias_cajas_del_mismo_dia_se_suman_en_el_resumen_diario(): void
    {
        $secondRegister = CashRegister::create([
            'branch_id' => $this->branch->id,
            'user_id' => $this->vendedor->id,
            'opened_at' => Carbon::parse('2026-10-10 18:00:00'),
            'opening_amount' => 300,
            'status' => 'cerrada',
        ]);

        $this->createSale(25);
        $this->createSale(75, register: $secondRegister);

        $this->actingAs($this->admin)
            ->get(route('reports.index', [
                'start_date' => '2026-10-10',
                'end_date' => '2026-10-10',
            ]))
            ->assertOk()
            ->assertViewHas('totalSalesCount', 2)
            ->assertViewHas('totalRevenue', 100.0)
            ->assertViewHas('totalRegisters', 2)
            ->assertViewHas('dailySummary', function ($dailySummary) {
                $day = $dailySummary->firstWhere('date', '2026-10-10');

                return $day !== null
                    && $day['registers'] === 2
                    && $day['count'] === 2
                    && $day['total'] === 100.0;
            });
    }

    public function test_los_gastos_y_unidades_tambien_se_cuentan_por_fecha_de_caja(): void
    {
        $this->createSale(100, profit: 40);

        $department = Department::create(['name' => 'Abarrotes']);
        $saleType = SaleType::create([
            'name' => 'Pieza',
            'base_unit' => 'pza',
            'is_active' => true,
        ]);
        $product = Product::create([
            'department_id' => $department->id,
            'barcode' => 'REP-1',
            'name' => 'Refresco',
            'sale_type_id' => $saleType->id,
            'unit_base' => 'pza',
            'price_retail' => 20,
            'price_wholesale' => 20,
            'price_super_wholesale' => 0,
            'cost' => 10,
            'is_active' => true,
        ]);

        SaleItem::create([
            'sale_id' => Sale::first()->id,
            'product_id' => $product->id,
            'quantity' => 3,
            'conversion_factor' => 2,
            'unit_price' => 20,
            'cost' => 10,
            'total' => 60,
        ]);

        Expense::create([
            'cash_register_id' => $this->backdatedRegister->id,
            'branch_id' => $this->branch->id,
            'user_id' => $this->vendedor->id,
            'category' => 'limpieza',
            'description' => 'Jerga',
            'amount' => 30,
        ]);

        $this->actingAs($this->admin)
            ->get(route('reports.index', [
                'start_date' => '2026-10-10',
                'end_date' => '2026-10-10',
            ]))
            ->assertOk()
            ->assertViewHas('unitsSold', 6.0)
            ->assertViewHas('totalExpenses', 30.0)
            ->assertViewHas('netProfit', 10.0)
            ->assertViewHas('dailySummary', function ($dailySummary) {
                $day = $dailySummary->firstWhere('date', '2026-10-10');

                return $day !== null
                    && $day['expenses'] === 30.0
                    && $day['net_profit'] === 10.0;
            });
    }

    public function test_admin_puede_cerrar_la_caja_con_una_fecha_editable(): void
    {
        $register = CashRegister::create([
            'branch_id' => $this->branch->id,
            'user_id' => $this->admin->id,
            'opened_at' => Carbon::parse('2026-10-10 09:00:00'),
            'opening_amount' => 500,
            'status' => 'abierta',
        ]);

        $this->actingAs($this->admin)
            ->post(route('cash-register.store-close'), [
                'closing_amount' => 650.00,
                'closed_at' => '2026-10-10',
            ])
            ->assertRedirect(route('cash-register.index'))
            ->assertSessionHas('success');

        $register->refresh();

        $this->assertSame('cerrada', $register->status);
        $this->assertSame('2026-10-10', $register->closed_at->toDateString());
        $this->assertSame('23:59:59', $register->closed_at->format('H:i:s'));
    }

    public function test_un_usuario_no_admin_no_puede_cambiar_la_fecha_de_cierre(): void
    {
        $register = CashRegister::create([
            'branch_id' => $this->branch->id,
            'user_id' => $this->vendedor->id,
            'opened_at' => now(),
            'opening_amount' => 100,
            'status' => 'abierta',
        ]);

        $this->actingAs($this->vendedor)
            ->post(route('cash-register.store-close'), [
                'closing_amount' => 100,
                'closed_at' => '2026-10-16',
            ])
            ->assertSessionHasErrors('closed_at');

        $this->assertSame('abierta', $register->fresh()->status);
    }

    public function test_la_fecha_de_cierre_no_puede_ser_anterior_a_la_apertura(): void
    {
        $register = CashRegister::create([
            'branch_id' => $this->branch->id,
            'user_id' => $this->admin->id,
            'opened_at' => Carbon::parse('2026-10-10 09:00:00'),
            'opening_amount' => 500,
            'status' => 'abierta',
        ]);

        $this->actingAs($this->admin)
            ->from(route('cash-register.index'))
            ->post(route('cash-register.store-close'), [
                'closing_amount' => 650.00,
                'closed_at' => '2026-10-09',
            ])
            ->assertRedirect(route('cash-register.index'))
            ->assertSessionHas('error');

        $this->assertSame('abierta', $register->fresh()->status);
    }

    public function test_el_campo_de_fecha_de_cierre_solo_se_muestra_al_admin(): void
    {
        CashRegister::create([
            'branch_id' => $this->branch->id,
            'user_id' => $this->admin->id,
            'opened_at' => now(),
            'opening_amount' => 500,
            'status' => 'abierta',
        ]);

        $this->actingAs($this->admin)
            ->get(route('cash-register.close'))
            ->assertOk()
            ->assertSee('Fecha de Cierre');

        $this->actingAs($this->vendedor)
            ->get(route('cash-register.close'))
            ->assertOk()
            ->assertDontSee('Fecha de Cierre');
    }
}
