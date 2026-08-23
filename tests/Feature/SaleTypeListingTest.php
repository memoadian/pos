<?php

namespace Tests\Feature;

use App\Models\SaleType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SaleTypeListingTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'Admin']);
        $this->admin = User::factory()->create(['username' => 'admin_tipos']);
        $this->admin->assignRole('Admin');
    }

    public function test_el_encabezado_y_el_resumen_muestran_el_total(): void
    {
        $this->createSaleTypes(3);

        $response = $this->actingAs($this->admin)->get(route('sale-types.index'));

        $response->assertOk();
        $this->assertSame(3, $response->viewData('totalSaleTypes'));
        $this->assertStringContainsString(
            '1–3 de 3 tipos de venta',
            $this->summaryText($response->viewData('summary')),
        );
    }

    public function test_el_resumen_aclara_cuando_el_conteo_viene_de_un_filtro(): void
    {
        $this->createSaleTypes(3);

        $response = $this->actingAs($this->admin)
            ->get(route('sale-types.index', ['search' => 'Tipo 1']));

        $summary = $this->summaryText($response->viewData('summary'));

        $this->assertStringContainsString('1–1 de 1 tipo de venta', $summary);
        $this->assertStringContainsString('filtrado de 3', $summary);
    }

    public function test_el_resumen_distingue_catalogo_vacio_de_busqueda_sin_resultados(): void
    {
        $vacio = $this->actingAs($this->admin)->get(route('sale-types.index'));
        $vacio->assertSee('Todavía no hay tipos de venta registrados', false);

        $this->createSaleTypes(2);

        $sinCoincidencias = $this->actingAs($this->admin)
            ->get(route('sale-types.index', ['search' => 'no-existe']));
        $sinCoincidencias->assertSee('Ningún tipo de venta coincide con los filtros', false);
    }

    public function test_el_ajax_devuelve_filas_paginado_y_resumen(): void
    {
        $this->createSaleTypes(3);

        $response = $this->actingAs($this->admin)
            ->getJson(route('sale-types.index'), ['X-Requested-With' => 'XMLHttpRequest']);

        // Antes devolvia solo el HTML de las filas: el resumen se quedaba viejo al filtrar.
        $response->assertOk()->assertJsonStructure(['rows', 'pagination', 'summary']);
        $this->assertStringContainsString('Tipo 1', $response->json('rows'));
        $this->assertStringContainsString('1–', $this->summaryText($response->json('summary')));
    }

    private function createSaleTypes(int $count): void
    {
        foreach (range(1, $count) as $i) {
            SaleType::create([
                'name' => 'Tipo '.$i,
                'base_unit' => 'pza',
                'is_active' => true,
            ]);
        }
    }

    /** El resumen se afirma por su texto: el markup lleva saltos de linea del Blade. */
    private function summaryText(string $html): string
    {
        return trim(preg_replace('/\s+/u', ' ', strip_tags($html)));
    }
}
