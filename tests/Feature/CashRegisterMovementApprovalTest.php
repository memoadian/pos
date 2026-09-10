<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\CashRegister;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CashRegisterMovementApprovalTest extends TestCase
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

        $this->admin = User::factory()->create(['username' => 'admin_mov', 'current_branch_id' => $this->branch->id]);
        $this->admin->assignRole('Admin');

        $this->cashier = User::factory()->create(['username' => 'cajero_mov', 'branch_id' => $this->branch->id]);
        $this->cashier->assignRole('Vendedor');
    }

    private function openRegister(): CashRegister
    {
        return CashRegister::create([
            'branch_id' => $this->branch->id,
            'user_id' => $this->cashier->id,
            'opened_at' => now()->subHour(),
            'opening_amount' => 500,
            'status' => 'abierta',
        ]);
    }

    private function addPendingMovement(CashRegister $register): \App\Models\CashRegisterMovement
    {
        return $register->movements()->create([
            'type' => 'retiro',
            'amount' => 50,
            'reason' => 'compra insumos',
            'user_id' => $this->cashier->id,
            'status' => 'pendiente',
        ]);
    }

    public function test_el_cajero_no_puede_cerrar_con_movimientos_pendientes(): void
    {
        $register = $this->openRegister();
        $this->addPendingMovement($register);

        $this->actingAs($this->cashier)
            ->post(route('cash-register.store-close'), ['closing_amount' => 450])
            ->assertRedirect();

        $this->assertSame('abierta', $register->fresh()->status);
    }

    public function test_el_admin_aprueba_el_movimiento_del_cajero_desde_el_detalle_y_este_ya_puede_cerrar(): void
    {
        $register = $this->openRegister();
        $movement = $this->addPendingMovement($register);

        // El admin ve los botones de aprobar en el detalle de la caja ajena
        $this->actingAs($this->admin)
            ->get(route('cash-register.show', $register))
            ->assertOk()
            ->assertSee('Aprobar');

        // Y aprueba (formulario, no AJAX -> redirect con flash)
        $this->actingAs($this->admin)
            ->from(route('cash-register.show', $register))
            ->post(route('cash-register.movement.approve', $movement))
            ->assertRedirect(route('cash-register.show', $register))
            ->assertSessionHas('success');

        $this->assertSame('aprobado', $movement->fresh()->status);

        // Ahora el cajero sí cierra
        $this->actingAs($this->cashier)
            ->post(route('cash-register.store-close'), ['closing_amount' => 450])
            ->assertRedirect(route('cash-register.index'))
            ->assertSessionHas('success');

        $this->assertSame('cerrada', $register->fresh()->status);
    }

    public function test_el_admin_rechaza_el_movimiento_desde_el_detalle(): void
    {
        $register = $this->openRegister();
        $movement = $this->addPendingMovement($register);

        $this->actingAs($this->admin)
            ->from(route('cash-register.show', $register))
            ->post(route('cash-register.movement.reject', $movement))
            ->assertRedirect(route('cash-register.show', $register))
            ->assertSessionHas('success');

        $this->assertSame('rechazado', $movement->fresh()->status);
    }

    public function test_un_cajero_sin_permiso_no_puede_aprobar_movimientos(): void
    {
        $register = $this->openRegister();
        $movement = $this->addPendingMovement($register);

        $this->actingAs($this->cashier)
            ->post(route('cash-register.movement.approve', $movement))
            ->assertForbidden();

        $this->assertSame('pendiente', $movement->fresh()->status);
    }

    public function test_el_cajero_no_ve_botones_de_aprobar_en_el_detalle(): void
    {
        $register = $this->openRegister();
        $this->addPendingMovement($register);

        // El cajero puede ver su propia caja pero sin acciones de aprobación
        $this->actingAs($this->cashier)
            ->get(route('cash-register.show', $register))
            ->assertOk()
            ->assertDontSee('onclick="decideMovement(', false);
    }

    public function test_el_historial_marca_las_cajas_con_movimientos_por_aprobar(): void
    {
        $register = $this->openRegister();
        $this->addPendingMovement($register);

        $this->actingAs($this->admin)
            ->get(route('cash-registers.history'))
            ->assertOk()
            ->assertSee('por aprobar');
    }
}
