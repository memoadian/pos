<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'username' => 'cajero1',
            'password' => Hash::make('secreta123'),
            'is_active' => true,
        ]);
    }

    public function test_recordar_sesion_inicia_sesion_y_deja_la_cookie(): void
    {
        // El navegador manda "on" en un checkbox sin value: es el caso real del formulario.
        $response = $this->post(route('login.post'), [
            'login' => 'cajero1',
            'password' => 'secreta123',
            'remember' => 'on',
        ]);

        $response->assertRedirect('/dashboard');
        $response->assertSessionHasNoErrors();
        $this->assertAuthenticatedAs($this->user);

        $response->assertCookie(Auth::guard()->getRecallerName());
        $this->assertNotNull($this->user->fresh()->remember_token);
    }

    public function test_sin_recordar_sesion_inicia_sesion_sin_cookie(): void
    {
        $response = $this->post(route('login.post'), [
            'login' => 'cajero1',
            'password' => 'secreta123',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($this->user);
        $response->assertCookieMissing(Auth::guard()->getRecallerName());
    }

    public function test_recordar_sesion_tambien_acepta_el_valor_1(): void
    {
        $this->post(route('login.post'), [
            'login' => 'cajero1',
            'password' => 'secreta123',
            'remember' => '1',
        ])->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($this->user);
    }

    public function test_credenciales_incorrectas_no_inician_sesion(): void
    {
        $this->post(route('login.post'), [
            'login' => 'cajero1',
            'password' => 'equivocada',
            'remember' => 'on',
        ])->assertSessionHasErrors('login');

        $this->assertGuest();
    }
}
