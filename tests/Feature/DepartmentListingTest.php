<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Product;
use App\Models\SaleType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DepartmentListingTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'Admin']);
        $this->admin = User::factory()->create(['username' => 'admin_departamentos']);
        $this->admin->assignRole('Admin');
    }

    public function test_el_encabezado_y_el_resumen_muestran_el_total(): void
    {
        $this->createDepartments(3);

        $response = $this->actingAs($this->admin)->get(route('departments.index'));

        $response->assertOk();
        $this->assertSame(3, $response->viewData('totalDepartments'));
        $this->assertStringContainsString(
            '1–3 de 3 departamentos',
            $this->summaryText($response->viewData('summary')),
        );
    }

    public function test_el_resumen_aclara_cuando_el_conteo_viene_de_un_filtro(): void
    {
        $this->createDepartments(3);

        $response = $this->actingAs($this->admin)
            ->get(route('departments.index', ['search' => 'Departamento 1']));

        $summary = $this->summaryText($response->viewData('summary'));

        $this->assertStringContainsString('1–1 de 1 departamento', $summary);
        $this->assertStringContainsString('filtrado de 3', $summary);
    }

    public function test_el_resumen_distingue_catalogo_vacio_de_busqueda_sin_resultados(): void
    {
        $vacio = $this->actingAs($this->admin)->get(route('departments.index'));
        $vacio->assertSee('Todavía no hay departamentos registrados', false);

        $this->createDepartments(2);

        $sinCoincidencias = $this->actingAs($this->admin)
            ->get(route('departments.index', ['search' => 'no-existe']));
        $sinCoincidencias->assertSee('Ningún departamento coincide con los filtros', false);
    }

    public function test_el_ajax_devuelve_filas_paginado_y_resumen(): void
    {
        $this->createDepartments(3);

        $response = $this->actingAs($this->admin)
            ->getJson(route('departments.index'), ['X-Requested-With' => 'XMLHttpRequest']);

        // Antes devolvia solo el HTML de las filas: el resumen y el paginado se quedaban viejos.
        $response->assertOk()->assertJsonStructure(['rows', 'pagination', 'summary']);
        $this->assertStringContainsString('Departamento 1', $response->json('rows'));
        $this->assertStringContainsString('1–', $this->summaryText($response->json('summary')));
    }

    public function test_los_links_de_paginado_conservan_la_busqueda(): void
    {
        $this->createDepartments(40);

        $response = $this->actingAs($this->admin)
            ->get(route('departments.index', ['search' => 'Departamento']));

        $this->assertStringContainsString(
            'search=Departamento',
            $response->viewData('departments')->nextPageUrl(),
        );
    }

    private function createDepartments(int $count): void
    {
        foreach (range(1, $count) as $i) {
            Department::create(['name' => 'Departamento '.$i]);
        }
    }

    /** El resumen se afirma por su texto: el markup lleva saltos de linea del Blade. */
    private function summaryText(string $html): string
    {
        return trim(preg_replace('/\s+/u', ' ', strip_tags($html)));
    }

    public function test_el_boton_de_borrar_se_apaga_y_dice_por_que_cuando_hay_productos(): void
    {
        $department = Department::create(['name' => 'Jarcería']);
        $saleType = SaleType::create(['name' => 'Pieza', 'base_unit' => 'pza', 'is_active' => true]);

        Product::create([
            'department_id' => $department->id,
            'barcode' => '111',
            'name' => 'Escoba',
            'sale_type_id' => $saleType->id,
            'unit_base' => 'pza',
            'price_retail' => 10.00,
            'price_wholesale' => 8.00,
            'price_super_wholesale' => 0,
            'cost' => 5.00,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->get(route('departments.index'));

        $response->assertOk();
        // Sin el estilo apagado el boton se veia igual que uno activo y el click se perdia.
        $response->assertSee('disabled:cursor-not-allowed', false);
        $response->assertSee('primero mueve o elimina sus 1 producto(s)', false);
    }

    public function test_un_departamento_vacio_deja_el_boton_de_borrar_activo(): void
    {
        Department::create(['name' => 'Vacío']);

        $response = $this->actingAs($this->admin)->get(route('departments.index'));

        $response->assertOk();
        $response->assertSee('title="Eliminar departamento"', false);
        $response->assertDontSee('primero mueve o elimina', false);
    }

    public function test_el_backend_bloquea_borrar_un_departamento_con_productos(): void
    {
        $department = Department::create(['name' => 'Jarcería']);
        $saleType = SaleType::create(['name' => 'Pieza', 'base_unit' => 'pza', 'is_active' => true]);

        Product::create([
            'department_id' => $department->id,
            'barcode' => '111',
            'name' => 'Escoba',
            'sale_type_id' => $saleType->id,
            'unit_base' => 'pza',
            'price_retail' => 10.00,
            'price_wholesale' => 8.00,
            'price_super_wholesale' => 0,
            'cost' => 5.00,
            'is_active' => true,
        ]);

        $this->actingAs($this->admin)
            ->delete(route('departments.destroy', $department))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('departments', ['id' => $department->id]);
    }
}
