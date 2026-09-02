<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\CashRegister;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ExpenseTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branch;

    private User $admin;

    private User $vendedor;

    private CashRegister $cashRegister;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'Admin']);
        Role::create(['name' => 'Manager']);
        Role::create(['name' => 'Vendedor']);

        $this->branch = Branch::create(['name' => 'Centro', 'address' => 'Calle 1', 'is_active' => true]);

        $this->admin = User::factory()->create(['username' => 'admin_exp', 'current_branch_id' => $this->branch->id]);
        $this->admin->assignRole('Admin');

        $this->vendedor = User::factory()->create(['username' => 'vendedor_exp', 'branch_id' => $this->branch->id]);
        $this->vendedor->assignRole('Vendedor');

        $this->cashRegister = CashRegister::create([
            'branch_id' => $this->branch->id,
            'user_id' => $this->vendedor->id,
            'opened_at' => now(),
            'opening_amount' => 500,
            'status' => 'abierta',
        ]);
    }

    public function test_registrar_gasto_requiere_caja_abierta(): void
    {
        $otro = User::factory()->create(['username' => 'sin_caja', 'branch_id' => $this->branch->id]);
        $otro->assignRole('Vendedor');

        $this->actingAs($otro)
            ->post(route('expenses.store'), ['category' => 'limpieza', 'description' => 'Jerga', 'amount' => 50])
            ->assertRedirect(route('cash-register.index'));

        $this->assertDatabaseCount('expenses', 0);
    }

    public function test_el_gasto_baja_el_monto_esperado_de_la_caja(): void
    {
        $this->actingAs($this->vendedor)
            ->postJson(route('expenses.store'), ['category' => 'limpieza', 'description' => 'Jerga y bolsas', 'amount' => 80])
            ->assertOk()
            ->assertJson(['success' => true]);

        $cr = $this->cashRegister->fresh();
        $this->assertEquals(80, $cr->total_expenses);
        // opening 500 + cash_sales 0 - expenses 80
        $this->assertEquals(420, $cr->expected_amount);
    }

    public function test_categoria_otro_guarda_la_descripcion(): void
    {
        $this->actingAs($this->vendedor)
            ->postJson(route('expenses.store'), ['category' => 'otro', 'description' => 'Propina al cargador', 'amount' => 30])
            ->assertOk();

        $this->assertDatabaseHas('expenses', [
            'category' => 'otro',
            'description' => 'Propina al cargador',
            'branch_id' => $this->branch->id,
            'user_id' => $this->vendedor->id,
        ]);
    }

    public function test_categoria_invalida_es_rechazada(): void
    {
        $this->actingAs($this->vendedor)
            ->postJson(route('expenses.store'), ['category' => 'viajes', 'description' => 'x', 'amount' => 10])
            ->assertStatus(422);
    }

    public function test_el_gasto_aparece_en_reportes_como_utilidad_neta(): void
    {
        Expense::create([
            'cash_register_id' => $this->cashRegister->id,
            'branch_id' => $this->branch->id,
            'user_id' => $this->vendedor->id,
            'category' => 'servicios',
            'description' => 'Recarga internet',
            'amount' => 200,
        ]);

        $this->actingAs($this->admin)
            ->get(route('reports.index'))
            ->assertOk()
            ->assertViewHas('totalExpenses', 200.0)
            ->assertViewHas('netProfit', -200.0);
    }

    public function test_la_pagina_de_gastos_filtra_por_categoria(): void
    {
        foreach ([['limpieza', 10], ['limpieza', 15], ['renta', 100]] as [$cat, $amount]) {
            Expense::create([
                'cash_register_id' => $this->cashRegister->id,
                'branch_id' => $this->branch->id,
                'user_id' => $this->vendedor->id,
                'category' => $cat,
                'description' => 'x',
                'amount' => $amount,
            ]);
        }

        $this->actingAs($this->admin)
            ->getJson(route('expenses.index', ['category' => 'limpieza']), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()
            ->assertJsonPath('period_total', money(25));
    }

    public function test_mi_caja_muestra_el_panel_de_gastos_del_dia(): void
    {
        Expense::create([
            'cash_register_id' => $this->cashRegister->id,
            'branch_id' => $this->branch->id,
            'user_id' => $this->vendedor->id,
            'category' => 'transporte',
            'description' => 'Gasolina reparto',
            'amount' => 250,
        ]);

        $this->actingAs($this->vendedor)
            ->get(route('cash-register.index'))
            ->assertOk()
            ->assertSee('Gastos del día')
            ->assertSee('Gasolina reparto')
            ->assertSee('Registrar Gasto');
    }

    public function test_un_vendedor_no_ve_la_pagina_de_gastos(): void
    {
        $this->actingAs($this->vendedor)
            ->get(route('expenses.index'))
            ->assertForbidden();
    }

    public function test_borrar_gasto_admin_siempre_creador_solo_con_caja_abierta(): void
    {
        $expense = Expense::create([
            'cash_register_id' => $this->cashRegister->id,
            'branch_id' => $this->branch->id,
            'user_id' => $this->vendedor->id,
            'category' => 'limpieza',
            'description' => 'x',
            'amount' => 40,
        ]);

        // Un tercero no puede
        $otro = User::factory()->create(['username' => 'tercero', 'branch_id' => $this->branch->id]);
        $otro->assignRole('Vendedor');
        $this->actingAs($otro)->delete(route('expenses.destroy', $expense))->assertForbidden();

        // El creador sí (su caja está abierta)
        $this->actingAs($this->vendedor)->delete(route('expenses.destroy', $expense))->assertRedirect();
        $this->assertDatabaseCount('expenses', 0);

        // Con la caja cerrada, el creador ya no puede; el Admin sí
        $expense2 = Expense::create([
            'cash_register_id' => $this->cashRegister->id,
            'branch_id' => $this->branch->id,
            'user_id' => $this->vendedor->id,
            'category' => 'renta',
            'description' => 'x',
            'amount' => 100,
        ]);
        $this->cashRegister->update(['status' => 'cerrada', 'closed_at' => now()]);

        $this->actingAs($this->vendedor)->delete(route('expenses.destroy', $expense2))->assertForbidden();
        $this->actingAs($this->admin)->delete(route('expenses.destroy', $expense2))->assertRedirect();
        $this->assertDatabaseCount('expenses', 0);
    }
}
