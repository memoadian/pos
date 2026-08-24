<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use App\Services\SettingsService;
use App\Support\ColorRamp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'Admin']);
        $this->admin = User::factory()->create(['username' => 'admin_settings']);
        $this->admin->assignRole('Admin');

        Storage::fake('public');
    }

    // --- SettingsService: guardado, cache y fallback a valores de fabrica ---

    public function test_un_ajuste_sin_guardar_regresa_su_valor_de_fabrica(): void
    {
        $this->assertSame('POS Limpieza', setting('site_name'));
        $this->assertSame('$', setting('currency_symbol'));
        $this->assertNull(setting('primary_color'));
    }

    public function test_guardar_un_ajuste_lo_hace_efectivo_de_inmediato(): void
    {
        app(SettingsService::class)->set('site_name', 'Ferre Don Beto');

        $this->assertSame('Ferre Don Beto', setting('site_name'));
    }

    public function test_borrar_un_ajuste_guardado_regresa_al_valor_de_fabrica(): void
    {
        $svc = app(SettingsService::class);
        $svc->set('site_name', 'Ferre Don Beto');
        $svc->set('site_name', null);

        // Sin esto, una fila con valor vacio "ganaba" para siempre sobre el
        // default de fabrica, incluso para llamadas que no repetian el default.
        $this->assertSame('POS Limpieza', setting('site_name'));
    }

    public function test_una_clave_desconocida_usa_el_default_del_llamador(): void
    {
        $this->assertSame('lo-que-sea', setting('clave_inexistente', 'lo-que-sea'));
    }

    public function test_los_ajustes_se_leen_de_cache_no_de_la_tabla(): void
    {
        app(SettingsService::class)->set('site_name', 'Cacheado');
        // set() solo invalida; la cache se calienta hasta la siguiente lectura.
        $this->assertSame('Cacheado', setting('site_name'));

        Setting::query()->where('key', 'site_name')->update(['value' => 'Editado a mano']);

        // Sin pasar por set()/setMany() la cache no se invalida: el valor
        // servido sigue siendo el que ya estaba cacheado antes del UPDATE directo.
        $this->assertSame('Cacheado', setting('site_name'));

        Cache::forget('settings.all');
        $this->assertSame('Editado a mano', setting('site_name'));
    }

    public function test_setmany_invalida_la_cache_una_sola_vez(): void
    {
        app(SettingsService::class)->setMany([
            'site_name' => 'Uno',
            'currency_symbol' => 'MX$',
        ]);

        $this->assertSame('Uno', setting('site_name'));
        $this->assertSame('MX$', setting('currency_symbol'));
    }

    // --- money() ---

    public function test_money_usa_el_simbolo_configurado(): void
    {
        $this->assertSame('$1,234.50', money(1234.5));

        app(SettingsService::class)->set('currency_symbol', 'MX$');
        $this->assertSame('MX$1,234.50', money(1234.5));
    }

    // --- ColorRamp ---

    public function test_color_ramp_extrae_el_mismo_hue_que_usa_tailwind_para_cyan(): void
    {
        // #0891b2 es el hex de cyan-600; si esto no da ~221.7 la paleta
        // resultante no coincidiria con el cyan de fabrica.
        $this->assertEqualsWithDelta(221.7, ColorRamp::hueFromHex('#0891b2'), 0.5);
    }

    public function test_color_ramp_devuelve_null_para_un_hex_invalido(): void
    {
        $this->assertNull(ColorRamp::hueFromHex('no-es-un-color'));
        $this->assertNull(ColorRamp::hueFromHex(null));
        $this->assertNull(ColorRamp::cssVariables('rgb(1,2,3)'));
    }

    public function test_color_ramp_genera_las_11_variables_de_la_paleta_cyan(): void
    {
        $css = ColorRamp::cssVariables('#f97316');

        foreach ([50, 100, 200, 300, 400, 500, 600, 700, 800, 900, 950] as $shade) {
            $this->assertStringContainsString("--color-cyan-{$shade}:oklch(", $css);
        }
    }

    // --- Pantalla de configuracion: autorizacion ---

    public function test_requiere_rol_admin_para_ver_la_pantalla(): void
    {
        $cashier = User::factory()->create(['username' => 'cajero_settings']);

        $this->actingAs($cashier)->get(route('settings.edit'))->assertForbidden();
    }

    public function test_requiere_rol_admin_para_guardar(): void
    {
        $cashier = User::factory()->create(['username' => 'cajero_settings_2']);

        $this->actingAs($cashier)
            ->put(route('settings.update'), ['site_name' => 'Hackeado'])
            ->assertForbidden();

        $this->assertSame('POS Limpieza', setting('site_name'));
    }

    public function test_admin_ve_la_pantalla_con_los_valores_actuales(): void
    {
        app(SettingsService::class)->set('site_name', 'Ferre Don Beto');

        $response = $this->actingAs($this->admin)->get(route('settings.edit'));

        $response->assertOk();
        $response->assertSee('Ferre Don Beto', false);
    }

    // --- Guardado ---

    public function test_guardar_actualiza_nombre_color_y_datos_del_negocio(): void
    {
        $response = $this->actingAs($this->admin)->put(route('settings.update'), [
            'site_name' => 'Ferre Don Beto',
            'primary_color' => '#f97316',
            'business_name' => 'Ferreteria Don Beto SA de CV',
            'business_address' => 'Av. Siempre Viva 123',
            'business_phone' => '55 1234 5678',
            'business_tax_id' => 'FDB010101AAA',
            'ticket_footer' => 'Vuelva pronto',
            'currency_symbol' => 'MX$',
        ]);

        $response->assertRedirect(route('settings.edit'));
        $response->assertSessionHas('success');

        $this->assertSame('Ferre Don Beto', setting('site_name'));
        $this->assertSame('#f97316', setting('primary_color'));
        $this->assertSame('Ferreteria Don Beto SA de CV', setting('business_name'));
        $this->assertSame('Av. Siempre Viva 123', setting('business_address'));
        $this->assertSame('55 1234 5678', setting('business_phone'));
        $this->assertSame('FDB010101AAA', setting('business_tax_id'));
        $this->assertSame('Vuelva pronto', setting('ticket_footer'));
        $this->assertSame('MX$', setting('currency_symbol'));
    }

    public function test_el_nombre_del_sitio_es_obligatorio(): void
    {
        $this->actingAs($this->admin)
            ->put(route('settings.update'), ['site_name' => ''])
            ->assertSessionHasErrors('site_name');
    }

    public function test_el_color_primario_debe_ser_un_hex_valido(): void
    {
        $this->actingAs($this->admin)
            ->put(route('settings.update'), ['site_name' => 'X', 'primary_color' => 'naranja'])
            ->assertSessionHasErrors('primary_color');
    }

    // --- Logo ---

    public function test_subir_un_logo_lo_guarda_y_lo_expone_por_storage_publico(): void
    {
        $file = UploadedFile::fake()->image('logo.png', 200, 200);

        $this->actingAs($this->admin)->put(route('settings.update'), [
            'site_name' => 'X',
            'logo' => $file,
        ]);

        $path = setting('logo_path');
        $this->assertNotNull($path);
        Storage::disk('public')->assertExists($path);
        $this->assertSame(Storage::disk('public')->url($path), app(SettingsService::class)->logoUrl());
    }

    public function test_subir_un_logo_nuevo_borra_el_anterior(): void
    {
        $this->actingAs($this->admin)->put(route('settings.update'), [
            'site_name' => 'X',
            'logo' => UploadedFile::fake()->image('viejo.png'),
        ]);
        $viejo = setting('logo_path');

        $this->actingAs($this->admin)->put(route('settings.update'), [
            'site_name' => 'X',
            'logo' => UploadedFile::fake()->image('nuevo.png'),
        ]);
        $nuevo = setting('logo_path');

        Storage::disk('public')->assertMissing($viejo);
        Storage::disk('public')->assertExists($nuevo);
    }

    public function test_quitar_el_logo_borra_el_archivo_y_la_referencia(): void
    {
        $this->actingAs($this->admin)->put(route('settings.update'), [
            'site_name' => 'X',
            'logo' => UploadedFile::fake()->image('logo.png'),
        ]);
        $path = setting('logo_path');

        $this->actingAs($this->admin)->put(route('settings.update'), [
            'site_name' => 'X',
            'remove_logo' => '1',
        ]);

        Storage::disk('public')->assertMissing($path);
        $this->assertNull(setting('logo_path'));
        $this->assertNull(app(SettingsService::class)->logoUrl());
    }

    public function test_un_archivo_que_no_es_imagen_se_rechaza(): void
    {
        $this->actingAs($this->admin)
            ->put(route('settings.update'), [
                'site_name' => 'X',
                'logo' => UploadedFile::fake()->create('virus.exe', 100),
            ])
            ->assertSessionHasErrors('logo');
    }

    // --- Integracion: donde se ve reflejado ---

    public function test_el_nombre_del_sitio_aparece_en_titulo_sidebar_y_login(): void
    {
        app(SettingsService::class)->set('site_name', 'Ferre Don Beto');

        $login = $this->get(route('login'));
        $login->assertSee('<title>Iniciar Sesión - Ferre Don Beto</title>', false);
        $login->assertSee('Ferre Don Beto');

        $dashboard = $this->actingAs($this->admin)->get(route('dashboard'));
        $dashboard->assertSee('Ferre Don Beto');
    }

    public function test_sin_color_configurado_no_se_emite_bloque_de_tema(): void
    {
        $this->get(route('login'))->assertDontSee(':root{', false);
    }

    public function test_con_color_configurado_el_bloque_de_tema_trae_el_hue_correcto(): void
    {
        app(SettingsService::class)->set('primary_color', '#f97316'); // naranja, hue ~47.6

        $response = $this->get(route('login'));

        $response->assertSee('--color-cyan-600:oklch(60.9% 0.126 47.6)', false);
    }

    public function test_sin_logo_el_sidebar_usa_el_icono_por_defecto(): void
    {
        $response = $this->actingAs($this->admin)->get(route('dashboard'));

        $response->assertSee('bi-shop', false);
    }

    public function test_con_logo_el_sidebar_y_el_login_usan_la_imagen(): void
    {
        $this->actingAs($this->admin)->put(route('settings.update'), [
            'site_name' => 'X',
            'logo' => UploadedFile::fake()->image('logo.png'),
        ]);

        $url = app(SettingsService::class)->logoUrl();

        // El login se revisa como invitado real: actingAs() del admin queda
        // vigente entre requests dentro del mismo test, y el login redirige
        // a quien ya tiene sesion.
        $this->post(route('logout'));
        $this->get(route('login'))->assertSee($url, false);

        $this->actingAs($this->admin)->get(route('dashboard'))->assertSee($url, false);
    }

    public function test_hay_un_enlace_a_configuracion_para_admin(): void
    {
        $this->actingAs($this->admin)->get(route('dashboard'))
            ->assertSee(route('settings.edit'), false);
    }
}
