<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\CashRegister;
use App\Models\Expense;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CashRegisterDeleteTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branch;

    private User $admin;

    private User $cashier;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'Admin']);
        Role::create(['name' => 'Vendedor']);

        $this->branch = Branch::create(['name' => 'Centro', 'address' => 'Calle 1', 'is_active' => true]);

        $this->admin = User::factory()->create(['username' => 'admin_del', 'current_branch_id' => $this->branch->id]);
        $this->admin->assignRole('Admin');

        $this->cashier = User::factory()->create(['username' => 'cajero_del', 'branch_id' => $this->branch->id]);
        $this->cashier->assignRole('Vendedor');
    }

    private function register(string $status = 'cerrada'): CashRegister
    {
        return CashRegister::create([
            'branch_id' => $this->branch->id,
            'user_id' => $this->cashier->id,
            'opened_at' => now()->subHours(3),
            'closed_at' => $status === 'cerrada' ? now() : null,
            'opening_amount' => 300,
            'status' => $status,
        ]);
    }

    public function test_admin_elimina_una_caja_sin_ventas_con_sus_movimientos_y_gastos(): void
    {
        $register = $this->register();
        $register->movements()->create([
            'type' => 'retiro', 'amount' => 50, 'reason' => 'prueba',
            'user_id' => $this->cashier->id, 'status' => 'pendiente',
        ]);
        Expense::create([
            'cash_register_id' => $register->id, 'branch_id' => $this->branch->id,
            'user_id' => $this->cashier->id, 'category' => 'limpieza', 'description' => 'x', 'amount' => 20,
        ]);

        $this->actingAs($this->admin)
            ->delete(route('cash-register.destroy', $register))
            ->assertRedirect(route('cash-registers.history'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('cash_registers', ['id' => $register->id]);
        $this->assertDatabaseMissing('cash_register_movements', ['cash_register_id' => $register->id]);
        $this->assertDatabaseMissing('expenses', ['cash_register_id' => $register->id]);
    }

    public function test_no_se_puede_eliminar_una_caja_con_ventas(): void
    {
        $register = $this->register();
        Sale::create([
            'branch_id' => $this->branch->id,
            'cash_register_id' => $register->id,
            'user_id' => $this->cashier->id,
            'subtotal' => 100, 'total' => 100, 'profit' => 40,
            'payment_method' => 'efectivo',
        ]);

        $this->actingAs($this->admin)
            ->delete(route('cash-register.destroy', $register))
            ->assertForbidden();

        $this->assertDatabaseHas('cash_registers', ['id' => $register->id]);
    }

    public function test_un_no_admin_no_puede_eliminar(): void
    {
        $register = $this->register();

        $this->actingAs($this->cashier)
            ->delete(route('cash-register.destroy', $register))
            ->assertForbidden();

        $this->assertDatabaseHas('cash_registers', ['id' => $register->id]);
    }

    public function test_el_historial_muestra_eliminar_solo_en_cajas_sin_ventas(): void
    {
        $vacia = $this->register();
        $conVenta = $this->register('abierta');
        Sale::create([
            'branch_id' => $this->branch->id,
            'cash_register_id' => $conVenta->id,
            'user_id' => $this->cashier->id,
            'subtotal' => 10, 'total' => 10, 'profit' => 3,
            'payment_method' => 'efectivo',
        ]);

        $html = $this->actingAs($this->admin)->get(route('cash-registers.history'))->assertOk()->getContent();

        $this->assertStringContainsString("deleteRegister({$vacia->id})", $html);
        $this->assertStringNotContainsString("deleteRegister({$conVenta->id})", $html);
    }
}
