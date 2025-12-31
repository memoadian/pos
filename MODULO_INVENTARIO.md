# Módulo de Inventario - Laravel 12

## Resumen de Implementación

El módulo de inventario ha sido completamente implementado siguiendo las mejores prácticas de Laravel y los requerimientos especificados.

---

## 1. Base de Datos

### Tabla: `inventories`

-   **id** (PK)
-   **product_id** (FK → products)
-   **branch_id** (FK → branches)
-   **stock_quantity** (decimal 10,2, default 0)
-   **created_at**, **updated_at**

**Índice único:** `(product_id, branch_id)` - Garantiza un solo registro por producto por sucursal.

**Migración:** `database/migrations/2025_12_30_095915_create_inventories_table.php`

### Tabla: `inventory_movements`

-   **id** (PK)
-   **product_id** (FK → products)
-   **branch_id** (FK → branches)
-   **user_id** (FK → users)
-   **type** (enum: 'IN', 'OUT', 'ADJUST')
-   **quantity** (decimal 10,2)
-   **reason** (string, nullable)
-   **created_at**, **updated_at**

**Tipos de movimiento:**

-   **IN**: Entrada de mercancía
-   **OUT**: Salida manual (merma, pérdida, uso interno)
-   **ADJUST**: Ajuste directo de stock (corrección)

**Migración:** `database/migrations/2025_12_30_095919_create_inventory_movements_table.php`

---

## 2. Modelos Eloquent

### `App\Models\Inventory`

**Ubicación:** `app/Models/Inventory.php`

**Características:**

-   Relaciones: `product()`, `branch()`
-   Validación en `boot()`: El stock_quantity **nunca puede ser negativo**
-   Cast: `stock_quantity` como decimal(2)

### `App\Models\InventoryMovement`

**Ubicación:** `app/Models/InventoryMovement.php`

**Características:**

-   Relaciones: `product()`, `branch()`, `user()`
-   Cast: `quantity` como decimal(2)
-   Registro automático de timestamps

---

## 3. Controladores

### `App\Http\Controllers\InventoryController`

**Ubicación:** `app/Http/Controllers/InventoryController.php`

**Método:** `index(Request $request)`

-   **Autorización:** Policy `viewAny`
-   **Filtros:**
    -   Por sucursal (branch)
    -   Por departamento (department)
    -   Por producto (product)
    -   Por stock bajo (low_stock)
    -   Búsqueda por nombre/código de barras (search)
-   **Soporte AJAX** para actualización dinámica de tabla

### `App\Http\Controllers\InventoryMovementController`

**Ubicación:** `app/Http/Controllers/InventoryMovementController.php`

**Métodos:**

#### `index(Request $request)`

-   Lista movimientos con filtros (sucursal, producto, tipo, rango de fechas)
-   Soporte AJAX

#### `create()`

-   **Autorización:** Policy `createMovement`
-   Muestra formulario para nuevo movimiento

#### `store(InventoryMovementRequest $request)`

-   **Autorización:** Policy `createMovement`
-   **Transacción DB:** Todo o nada
-   **Lógica:**
    1. Verifica sucursal activa
    2. Obtiene o crea inventario (FirstOrCreate)
    3. Calcula nuevo stock según tipo:
        - **IN**: suma quantity
        - **OUT**: resta quantity
        - **ADJUST**: establece quantity directamente
    4. Valida que stock no sea negativo
    5. Actualiza inventario
    6. Registra movimiento

---

## 4. Validación de Datos

### `App\Http\Requests\InventoryMovementRequest`

**Ubicación:** `app/Http/Requests/InventoryMovementRequest.php`

**Reglas:**

```php
'product_id' => 'required|exists:products,id',
'branch_id' => 'required|exists:branches,id',
'type' => 'required|in:IN,OUT,ADJUST',
'quantity' => 'required|numeric|min:0.01',
'reason' => 'required|string|max:500',
```

**Mensajes personalizados en español.**

---

## 5. Autorización (Policies)

### `App\Policies\InventoryPolicy`

**Ubicación:** `app/Policies/InventoryPolicy.php`

**Métodos:**

| Método             | Descripción                       | Acceso                          |
| ------------------ | --------------------------------- | ------------------------------- |
| `viewAny()`        | Ver listado de inventario         | Todos los usuarios autenticados |
| `view()`           | Ver inventario específico         | Todos los usuarios autenticados |
| `createMovement()` | Crear movimientos (IN/OUT/ADJUST) | Admin, Admin                    |
| `adjustStock()`    | Ajustar stock directamente        | Admin, Admin                    |

**Registrado en:** `app/Providers/AppServiceProvider.php`

---

## 6. Permisos (Spatie Permission)

### Permisos del Grupo "Inventario"

**Seeder:** `database/seeders/RolePermissionSeeder.php`

| Permiso                            | Descripción                                        |
| ---------------------------------- | -------------------------------------------------- |
| `ver inventario`                   | Permite ver el inventario de productos             |
| `registrar movimientos inventario` | Permite registrar entradas y salidas de inventario |
| `ajustar stock`                    | Permite ajustar el stock directamente              |

### Roles y Asignaciones

| Rol          | Permisos de Inventario |
| ------------ | ---------------------- |
| **Empleado** | `ver inventario`       |
| **Admin**    | Todos los permisos     |
| **Admin**    | Todos los permisos     |

---

## 7. Observadores (Observers)

### `App\Observers\ProductObserver`

**Ubicación:** `app/Observers/ProductObserver.php`

**Evento:** `created(Product $product)`

**Acción:** Al crear un producto, automáticamente crea un registro de inventario con `stock_quantity = 0` en **todas las sucursales activas**.

**Registrado en:** `app/Providers/AppServiceProvider.php`

---

## 8. Vistas

### Vista Principal de Inventario

**Ubicación:** `resources/views/inventory/index.blade.php`

**Características:**

-   Selector de sucursal
-   Filtros por departamento, stock bajo, búsqueda
-   Tabla con productos, departamento, stock, unidad
-   Resalta en amarillo productos con stock ≤ 10
-   Actualización AJAX sin recargar página

**Partial:** `resources/views/inventory/partials/table-rows.blade.php`

### Vistas de Movimientos

#### Listado

**Ubicación:** `resources/views/inventory-movements/index.blade.php`

**Características:**

-   Filtros: sucursal, producto, tipo, rango de fechas
-   Tabla con: fecha, tipo, producto, sucursal, cantidad, motivo, usuario
-   Badges de colores por tipo:
    -   Verde: Entrada
    -   Rojo: Salida
    -   Azul: Ajuste

**Partial:** `resources/views/inventory-movements/partials/table-rows.blade.php`

#### Crear Movimiento

**Ubicación:** `resources/views/inventory-movements/create.blade.php`

**Campos:**

-   Sucursal (select)
-   Producto (select)
-   Tipo (select: IN/OUT/ADJUST)
-   Cantidad (number, step 0.01, min 0.01)
-   Motivo (textarea, max 500 chars)

---

## 9. Rutas

**Archivo:** `routes/web.php`

```php
// Solo Admin y Admin
Route::middleware(['auth', 'role:Admin|Admin'])->group(function () {

    // Inventario (solo lectura)
    Route::get('inventory', [InventoryController::class, 'index'])
        ->name('inventory.index');

    // Movimientos de Inventario
    Route::resource('inventory-movements', InventoryMovementController::class)
        ->only(['index', 'create', 'store']);
});
```

---

## 10. Reglas de Negocio

### Regla de Oro

**El stock NUNCA se modifica directamente en `inventories.stock_quantity`.**

Todo cambio debe hacerse a través de `inventory_movements`:

1. Crear el movimiento
2. Actualizar el inventario
3. Ambos en una **transacción** (DB::transaction)

### Validaciones Críticas

1. **Stock nunca negativo:**

    - Validación en el modelo `Inventory::boot()`
    - Validación en el controller antes de actualizar

2. **Unicidad producto-sucursal:**

    - Índice único en base de datos
    - `firstOrCreate` en controller

3. **Transaccionalidad:**

    - Todo cambio usa `DB::beginTransaction()`
    - Si falla, hace `rollBack()`
    - Si tiene éxito, hace `commit()`

4. **Sucursales activas:**
    - Solo se pueden hacer movimientos en sucursales activas
    - Validado en el controller

---

## 11. Instrucciones de Despliegue

### Paso 1: Iniciar Docker (si no está corriendo)

```bash
docker compose up -d
```

### Paso 2: Ejecutar Migraciones

```bash
docker compose exec pos-app php artisan migrate:fresh --seed
```

**Importante:** `migrate:fresh` borra toda la base de datos y la recrea. Si ya tienes datos, usa:

```bash
docker compose exec pos-app php artisan migrate
docker compose exec pos-app php artisan db:seed --class=RolePermissionSeeder
```

### Paso 3: Verificar Permisos

```bash
docker compose exec pos-app php artisan permission:cache-reset
```

---

## 12. Testing Manual

### Probar Creación de Producto

1. Crear un nuevo producto
2. Verificar que se creó inventario en todas las sucursales con stock 0

### Probar Movimiento de Entrada (IN)

1. Ir a "Movimientos de Inventario" → "Nuevo Movimiento"
2. Seleccionar sucursal, producto
3. Tipo: "Entrada"
4. Cantidad: 100
5. Motivo: "Compra inicial"
6. Verificar que el stock aumentó en el inventario

### Probar Movimiento de Salida (OUT)

1. Crear movimiento tipo "Salida"
2. Cantidad: 20
3. Verificar que el stock disminuyó

### Probar Validación de Stock Negativo

1. Intentar crear salida mayor al stock disponible
2. Debe rechazarse con mensaje de error

### Probar Ajuste (ADJUST)

1. Crear movimiento tipo "Ajuste"
2. Cantidad: 50
3. Verificar que el stock ahora es exactamente 50 (no suma/resta, establece)

### Probar Permisos

1. Crear usuario con rol "Empleado"
2. Iniciar sesión
3. Debe poder ver inventario
4. NO debe poder crear movimientos (403 Forbidden)

---

## 13. Archivos Modificados/Creados

### Creados

-   `app/Models/Inventory.php` ✅
-   `app/Models/InventoryMovement.php` ✅
-   `app/Http/Controllers/InventoryController.php` ✅
-   `app/Http/Controllers/InventoryMovementController.php` ✅
-   `app/Http/Requests/InventoryMovementRequest.php` ✅
-   `app/Policies/InventoryPolicy.php` ✅
-   `app/Observers/ProductObserver.php` ✅
-   `database/migrations/*_create_inventories_table.php` ✅
-   `database/migrations/*_create_inventory_movements_table.php` ✅
-   `resources/views/inventory/index.blade.php` ✅
-   `resources/views/inventory/partials/table-rows.blade.php` ✅
-   `resources/views/inventory-movements/index.blade.php` ✅
-   `resources/views/inventory-movements/create.blade.php` ✅
-   `resources/views/inventory-movements/partials/table-rows.blade.php` ✅

### Modificados

-   `app/Providers/AppServiceProvider.php` (registrar Policy y Observer) ✅
-   `database/seeders/RolePermissionSeeder.php` (agregar permisos de inventario) ✅
-   `routes/web.php` (agregar rutas de inventario) ✅

---

## 14. Consideraciones Técnicas Cumplidas

✅ Uso de Eloquent Models con relaciones
✅ DB::transaction() para operaciones críticas
✅ Índice único (product_id, branch_id)
✅ Policies para autorización
✅ Form Request para validación
✅ El stock nunca se modifica directamente
✅ Siempre mediante inventory_movements
✅ Stock nunca negativo (validado en modelo y controller)
✅ Inicialización automática de inventario al crear producto
✅ Vista con filtros funcionales
✅ Permisos con Spatie configurados

---

## Contacto y Soporte

Para dudas o problemas con el módulo de inventario, revisar:

-   Logs de Laravel: `storage/logs/laravel.log`
-   Validaciones en: `app/Http/Requests/InventoryMovementRequest.php`
-   Lógica de negocio en: `app/Http/Controllers/InventoryMovementController.php`

---

**Módulo completado el:** 30 de Diciembre, 2025
**Framework:** Laravel 12
**Base de datos:** MySQL 8.0
**Permisos:** Spatie Laravel Permission 6.24
