<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\CashRegister;
use App\Models\Department;
use App\Models\Product;
use App\Models\ProductAlias;
use App\Models\SaleType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProductAliasTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Branch $branch;

    private Department $department;

    private SaleType $saleType;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'Admin']);
        $this->branch = Branch::create(['name' => 'Centro', 'address' => 'Calle 1', 'is_active' => true]);

        $this->admin = User::factory()->create([
            'username' => 'admin_alias',
            'current_branch_id' => $this->branch->id,
        ]);
        $this->admin->assignRole('Admin');

        $this->department = Department::create(['name' => 'Limpieza']);
        $this->saleType = SaleType::create(['name' => 'Pieza', 'base_unit' => 'pza', 'is_active' => true]);
    }

    public function test_buscar_por_alias_encuentra_el_producto(): void
    {
        $aromatizante = $this->createProduct('Aromatizante', '111');
        $aromatizante->syncAliases(['dogo', 'chupiral', 'lubrvinal']);

        $this->createProduct('Cloro', '222');

        $response = $this->actingAs($this->admin)
            ->get(route('products.index', ['search' => 'chupiral']));

        $productos = $response->viewData('products');

        $this->assertCount(1, $productos);
        $this->assertSame($aromatizante->id, $productos->first()->id);
    }

    public function test_la_busqueda_por_alias_es_parcial_y_sin_distinguir_mayusculas(): void
    {
        $producto = $this->createProduct('Aromatizante', '111');
        $producto->syncAliases(['Chupiral']);

        foreach (['chupi', 'CHUPIRAL', 'piral'] as $term) {
            $encontrados = $this->actingAs($this->admin)
                ->get(route('products.index', ['search' => $term]))
                ->viewData('products');

            $this->assertCount(1, $encontrados, "Buscando \"{$term}\"");
        }
    }

    public function test_la_busqueda_sigue_encontrando_por_nombre_y_codigo(): void
    {
        $producto = $this->createProduct('Aromatizante', '7501234567890');
        $producto->syncAliases(['dogo']);

        foreach (['Aromatiz', '7501234567890'] as $term) {
            $encontrados = $this->actingAs($this->admin)
                ->get(route('products.index', ['search' => $term]))
                ->viewData('products');

            $this->assertCount(1, $encontrados, "Buscando \"{$term}\"");
        }
    }

    public function test_varios_productos_pueden_compartir_un_alias_de_marca(): void
    {
        $this->createProduct('Aromatizante', '111')->syncAliases(['dogo']);
        $this->createProduct('Limpiador', '222')->syncAliases(['dogo']);

        $encontrados = $this->actingAs($this->admin)
            ->get(route('products.index', ['search' => 'dogo']))
            ->viewData('products');

        $this->assertCount(2, $encontrados);
    }

    public function test_buscar_por_alias_no_dispara_una_consulta_por_producto(): void
    {
        $this->actingAs($this->admin);
        $this->crearProductosConAlias(3, 'dogo');

        // Primera corrida sin medir: deja calientes los caches de rol y permisos,
        // que si no se cobran una sola vez y ensucian la comparacion.
        $this->get(route('products.index', ['search' => 'dogo']))->assertOk();

        $conPocos = $this->contarConsultasBuscando('dogo');

        $this->crearProductosConAlias(40, 'dogo');

        $conMuchos = $this->contarConsultasBuscando('dogo');

        // El alias entra como un EXISTS dentro de la misma consulta y la relacion
        // se trae con eager loading: el costo no depende de cuantos productos haya.
        $this->assertSame(
            $conPocos,
            $conMuchos,
            "Con 3 productos: {$conPocos} consultas; con 43: {$conMuchos}",
        );
    }

    private function crearProductosConAlias(int $cuantos, string $term): void
    {
        $desde = Product::count() + 1;

        foreach (range($desde, $desde + $cuantos - 1) as $i) {
            $this->createProduct('Producto '.$i, (string) (1000 + $i))->syncAliases([$term.' '.$i]);
        }
    }

    private function contarConsultasBuscando(string $term): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->get(route('products.index', ['search' => $term]))->assertOk();

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        return count($queries);
    }

    public function test_el_alta_guarda_los_alias_capturados(): void
    {
        $this->actingAs($this->admin)
            ->post(route('products.store'), $this->productPayload([
                'aliases' => ['dogo', 'chupiral'],
            ]))
            ->assertRedirect(route('products.index'));

        $producto = Product::where('barcode', '999')->firstOrFail();

        $this->assertEqualsCanonicalizing(
            ['dogo', 'chupiral'],
            $producto->aliases->pluck('alias')->all(),
        );
    }

    public function test_editar_reemplaza_los_alias_y_no_los_acumula(): void
    {
        $producto = $this->createProduct('Aromatizante', '999');
        $producto->syncAliases(['dogo', 'viejo']);

        $this->actingAs($this->admin)
            ->put(route('products.update', $producto), $this->productPayload([
                'aliases' => ['dogo', 'nuevo'],
            ]))
            ->assertRedirect(route('products.index'));

        $this->assertEqualsCanonicalizing(
            ['dogo', 'nuevo'],
            $producto->fresh()->aliases->pluck('alias')->all(),
        );

        // El que se conservo no se borro y volvio a crear: sigue siendo la misma fila.
        $this->assertSame(2, ProductAlias::where('product_id', $producto->id)->count());
    }

    public function test_se_ignoran_los_alias_vacios_y_repetidos(): void
    {
        $producto = $this->createProduct('Aromatizante', '999');

        // El unique de la tabla es lo ultimo que atrapa; el modelo limpia antes.
        $producto->syncAliases(['dogo', '  dogo  ', 'DOGO', '', '   ', 'chupiral']);

        $this->assertEqualsCanonicalizing(
            ['dogo', 'chupiral'],
            $producto->aliases->pluck('alias')->all(),
        );
    }

    public function test_borrar_el_producto_se_lleva_sus_alias(): void
    {
        $producto = $this->createProduct('Aromatizante', '999');
        $producto->syncAliases(['dogo']);

        $producto->delete();

        $this->assertSame(0, ProductAlias::count());
    }

    public function test_el_buscador_del_pos_encuentra_por_alias(): void
    {
        $producto = $this->createProduct('Aromatizante', '111');
        $producto->syncAliases(['chupiral']);

        // El POS exige caja abierta en la sucursal en curso
        CashRegister::create([
            'branch_id' => $this->branch->id,
            'user_id' => $this->admin->id,
            'opened_at' => now(),
            'opening_amount' => 100,
            'status' => 'abierta',
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson(route('pos.products.search', ['query' => 'chupiral']));

        $response->assertOk();

        $this->assertSame($producto->id, $response->json('products.0.id'));
        // El resultado carga el alias para que el cajero vea por que hizo match.
        $this->assertSame(['chupiral'], $response->json('products.0.aliases'));
    }

    private function createProduct(string $name, string $barcode): Product
    {
        return Product::create([
            'department_id' => $this->department->id,
            'barcode' => $barcode,
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

    /** @param array<string, mixed> $overrides */
    private function productPayload(array $overrides = []): array
    {
        return array_merge([
            'department_id' => $this->department->id,
            'barcode' => '999',
            'name' => 'Aromatizante',
            'sale_type_ids' => [$this->saleType->id],
            'default_sale_type_id' => $this->saleType->id,
            'price_retail' => 10.00,
            'price_wholesale' => 8.00,
            'price_super_wholesale' => 0,
            'cost' => 5.00,
            'is_active' => 1,
        ], $overrides);
    }
}
