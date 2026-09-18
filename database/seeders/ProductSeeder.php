<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Department;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\SaleType;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Catálogo de demostración: unos cuantos productos por departamento, con
     * los tipos de venta que ya trae el sistema (Pieza, Litros, Kilogramos).
     * Requiere que DepartmentSeeder, SaleTypeSeeder y BranchSeeder ya hayan
     * corrido.
     */
    public function run(): void
    {
        $departments = Department::pluck('id', 'name');
        $saleTypes = SaleType::pluck('id', 'name');
        $baseUnits = SaleType::pluck('base_unit', 'id');
        $branches = Branch::where('is_active', true)->pluck('id');

        if ($departments->isEmpty() || $saleTypes->isEmpty() || $branches->isEmpty()) {
            $this->command->warn('Faltan departamentos, tipos de venta o sucursales: corre esos seeders primero.');

            return;
        }

        $products = [
            // Jarcería
            ['barcode' => '7501234560001', 'department' => 'Jarcería', 'name' => 'Cloro', 'sale_type' => 'Litros', 'cost' => 8, 'price_retail' => 15, 'price_wholesale' => 13, 'price_super_wholesale' => 12, 'min_wholesale_qty' => 5, 'min_super_wholesale_qty' => 12, 'stock' => 40],
            ['barcode' => '7501234560002', 'department' => 'Jarcería', 'name' => 'Fabuloso', 'sale_type' => 'Pieza', 'cost' => 18, 'price_retail' => 28, 'price_wholesale' => 25, 'price_super_wholesale' => 23, 'min_wholesale_qty' => 6, 'min_super_wholesale_qty' => 12, 'stock' => 30],
            ['barcode' => '7501234560003', 'department' => 'Jarcería', 'name' => 'Jabón para trastes', 'sale_type' => 'Pieza', 'cost' => 10, 'price_retail' => 16, 'price_wholesale' => 14, 'price_super_wholesale' => null, 'min_wholesale_qty' => 12, 'min_super_wholesale_qty' => null, 'stock' => 50],
            ['barcode' => '7501234560004', 'department' => 'Jarcería', 'name' => 'Detergente en polvo', 'sale_type' => 'Kilogramos', 'cost' => 20, 'price_retail' => 32, 'price_wholesale' => 28, 'price_super_wholesale' => 26, 'min_wholesale_qty' => 5, 'min_super_wholesale_qty' => 15, 'stock' => 25],
            ['barcode' => '7501234560005', 'department' => 'Jarcería', 'name' => 'Escoba', 'sale_type' => 'Pieza', 'cost' => 35, 'price_retail' => 55, 'price_wholesale' => null, 'price_super_wholesale' => null, 'min_wholesale_qty' => null, 'min_super_wholesale_qty' => null, 'stock' => 15],
            ['barcode' => '7501234560006', 'department' => 'Jarcería', 'name' => 'Trapeador', 'sale_type' => 'Pieza', 'cost' => 40, 'price_retail' => 65, 'price_wholesale' => null, 'price_super_wholesale' => null, 'min_wholesale_qty' => null, 'min_super_wholesale_qty' => null, 'stock' => 12],
            ['barcode' => '7501234560007', 'department' => 'Jarcería', 'name' => 'Papel higiénico (paquete)', 'sale_type' => 'Pieza', 'cost' => 45, 'price_retail' => 68, 'price_wholesale' => 62, 'price_super_wholesale' => null, 'min_wholesale_qty' => 10, 'min_super_wholesale_qty' => null, 'stock' => 35],
            ['barcode' => '7501234560008', 'department' => 'Jarcería', 'name' => 'Cubeta 19L', 'sale_type' => 'Pieza', 'cost' => 30, 'price_retail' => 48, 'price_wholesale' => null, 'price_super_wholesale' => null, 'min_wholesale_qty' => null, 'min_super_wholesale_qty' => null, 'stock' => 10],

            // Dogo
            ['barcode' => '7501234570001', 'department' => 'Dogo', 'name' => 'Croquetas para perro', 'sale_type' => 'Kilogramos', 'cost' => 22, 'price_retail' => 35, 'price_wholesale' => 30, 'price_super_wholesale' => 27, 'min_wholesale_qty' => 5, 'min_super_wholesale_qty' => 20, 'stock' => 60],
            ['barcode' => '7501234570002', 'department' => 'Dogo', 'name' => 'Croquetas para gato', 'sale_type' => 'Kilogramos', 'cost' => 25, 'price_retail' => 38, 'price_wholesale' => 33, 'price_super_wholesale' => 30, 'min_wholesale_qty' => 5, 'min_super_wholesale_qty' => 20, 'stock' => 45],
            ['barcode' => '7501234570003', 'department' => 'Dogo', 'name' => 'Arena para gato', 'sale_type' => 'Kilogramos', 'cost' => 8, 'price_retail' => 14, 'price_wholesale' => 12, 'price_super_wholesale' => null, 'min_wholesale_qty' => 10, 'min_super_wholesale_qty' => null, 'stock' => 50],
            ['barcode' => '7501234570004', 'department' => 'Dogo', 'name' => 'Hueso natural para perro', 'sale_type' => 'Pieza', 'cost' => 12, 'price_retail' => 20, 'price_wholesale' => null, 'price_super_wholesale' => null, 'min_wholesale_qty' => null, 'min_super_wholesale_qty' => null, 'stock' => 25],
            ['barcode' => '7501234570005', 'department' => 'Dogo', 'name' => 'Shampoo para mascota', 'sale_type' => 'Pieza', 'cost' => 25, 'price_retail' => 40, 'price_wholesale' => null, 'price_super_wholesale' => null, 'min_wholesale_qty' => null, 'min_super_wholesale_qty' => null, 'stock' => 18],
            ['barcode' => '7501234570006', 'department' => 'Dogo', 'name' => 'Correa para perro', 'sale_type' => 'Pieza', 'cost' => 30, 'price_retail' => 50, 'price_wholesale' => null, 'price_super_wholesale' => null, 'min_wholesale_qty' => null, 'min_super_wholesale_qty' => null, 'stock' => 14],
        ];

        foreach ($products as $data) {
            if (! $departments->has($data['department']) || ! $saleTypes->has($data['sale_type'])) {
                $this->command->warn("Se omite \"{$data['name']}\": falta el departamento o tipo de venta \"{$data['department']}\"/\"{$data['sale_type']}\".");

                continue;
            }

            $saleTypeId = $saleTypes[$data['sale_type']];

            $product = Product::updateOrCreate(
                ['barcode' => $data['barcode']],
                [
                    'department_id' => $departments[$data['department']],
                    'name' => $data['name'],
                    'sale_type_id' => $saleTypeId,
                    'unit_base' => $baseUnits[$saleTypeId],
                    'cost' => $data['cost'],
                    'price_retail' => $data['price_retail'],
                    'price_wholesale' => $data['price_wholesale'] ?? $data['price_retail'],
                    'price_super_wholesale' => $data['price_super_wholesale'] ?? 0,
                    'min_wholesale_qty' => $data['min_wholesale_qty'],
                    'min_super_wholesale_qty' => $data['min_super_wholesale_qty'],
                    'is_active' => true,
                ]
            );

            // Stock de ejemplo repartido en las sucursales: la primera se
            // queda con el stock "normal" del catálogo, las demás con la
            // mitad, para que no todas las sucursales luzcan idénticas.
            foreach ($branches as $index => $branchId) {
                $stock = $index === 0 ? $data['stock'] : round($data['stock'] / 2, 2);

                Inventory::updateOrCreate(
                    ['product_id' => $product->id, 'branch_id' => $branchId],
                    ['stock_quantity' => $stock]
                );
            }

            $this->command->info("✓ Producto creado/actualizado: {$data['name']}");
        }

        // Sucursal Sur con menos de 1 litro de Cloro: muestra que vender la
        // fraccion que queda ya funciona (antes el POS lo rechazaba).
        $cloro = Product::where('barcode', '7501234560001')->first();
        $sucursalSur = Branch::where('name', 'Sucursal Sur')->value('id');

        if ($cloro && $sucursalSur) {
            Inventory::updateOrCreate(
                ['product_id' => $cloro->id, 'branch_id' => $sucursalSur],
                ['stock_quantity' => 0.75]
            );

            $this->command->info('✓ Cloro en Sucursal Sur ajustado a 0.75 L (demo de venta por fracción)');
        }
    }
}
