<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ejemplos de Uso - {{ $permission->name }}</title>
    @vite(['resources/css/app.css'])
</head>
<body class="bg-slate-50 p-6">
    <div class="max-w-3xl mx-auto space-y-4">
        <!-- Header -->
        <div>
            <h1 class="text-lg font-semibold text-slate-900">Ejemplos de Uso del Permiso</h1>
            <p class="text-sm text-slate-600 mt-2">
                Permiso: <code class="px-2 py-0.5 bg-slate-200 rounded text-xs font-medium">{{ $permission->name }}</code>
            </p>
            @if($permission->description)
            <p class="text-sm text-slate-600 mt-1">{{ $permission->description }}</p>
            @endif
        </div>

        <hr class="border-slate-200">

        <!-- Blade Templates -->
        <div class="bg-white rounded-lg border border-slate-200 p-4">
            <h2 class="text-sm font-semibold text-slate-900 mb-3">En plantillas Blade</h2>
            <div class="bg-slate-900 rounded-lg p-4 overflow-x-auto">
                <pre class="text-xs text-emerald-400 font-mono"><code>&#x3C;!-- Mostrar solo si tiene el permiso --&#x3E;
@can('{{ $permission->name }}')
    &#x3C;button class="btn btn-primary"&#x3E;
        Realizar acción
    &#x3C;/button&#x3E;
@endcan

&#x3C;!-- Mostrar si NO tiene el permiso --&#x3E;
@cannot('{{ $permission->name }}')
    &#x3C;p class="text-red-600"&#x3E;
        No tienes permiso para realizar esta acción
    &#x3C;/p&#x3E;
@endcannot</code></pre>
            </div>
        </div>

        <!-- Controllers -->
        <div class="bg-white rounded-lg border border-slate-200 p-4">
            <h2 class="text-sm font-semibold text-slate-900 mb-3">En controladores</h2>
            <div class="bg-slate-900 rounded-lg p-4 overflow-x-auto">
                <pre class="text-xs text-emerald-400 font-mono"><code>// Verificar si el usuario tiene permiso
if (auth()->user()->can('{{ $permission->name }}')) {
    // Realizar acción protegida
}

// Lanzar excepción 403 si no tiene permiso
$this->authorize('{{ $permission->name }}');

// En el constructor del controlador (middleware)
public function __construct()
{
    $this->middleware('permission:{{ $permission->name }}');
}</code></pre>
            </div>
        </div>

        <!-- Routes -->
        <div class="bg-white rounded-lg border border-slate-200 p-4">
            <h2 class="text-sm font-semibold text-slate-900 mb-3">En rutas</h2>
            <div class="bg-slate-900 rounded-lg p-4 overflow-x-auto">
                <pre class="text-xs text-emerald-400 font-mono"><code>// Proteger toda una sección de rutas
Route::middleware(['auth', 'permission:{{ $permission->name }}'])->group(function () {
    Route::get('/ruta-protegida', [Controller::class, 'metodo']);
    Route::post('/crear', [Controller::class, 'store']);
});

// O proteger una ruta individual
Route::get('/ruta', [Controller::class, 'metodo'])
    ->middleware('permission:{{ $permission->name }}');</code></pre>
            </div>
        </div>

        <!-- Policies -->
        <div class="bg-white rounded-lg border border-slate-200 p-4">
            <h2 class="text-sm font-semibold text-slate-900 mb-3">En Policies</h2>
            <div class="bg-slate-900 rounded-lg p-4 overflow-x-auto">
                <pre class="text-xs text-emerald-400 font-mono"><code>// En una clase Policy
public function create(User $user)
{
    return $user->can('{{ $permission->name }}');
}

// Uso en controlador
public function store(Request $request)
{
    $this->authorize('create', Model::class);
    // ...
}</code></pre>
            </div>
        </div>

        <!-- Gates -->
        <div class="bg-white rounded-lg border border-slate-200 p-4">
            <h2 class="text-sm font-semibold text-slate-900 mb-3">Información Adicional</h2>
            <div class="space-y-2 text-sm text-slate-600">
                <p><strong>Grupo:</strong> {{ $permission->group }}</p>
                <p><strong>Guard:</strong> web</p>
                <p><strong>Nombre de código:</strong> <code class="bg-slate-100 px-2 py-0.5 rounded text-xs">{{ $permission->name }}</code></p>
                <p class="mt-3 pt-3 border-t border-slate-200">
                    <strong>Nota:</strong> Los permisos se cachean en Laravel. Si haces cambios, ejecuta:
                    <code class="bg-slate-100 px-2 py-0.5 rounded text-xs block mt-1">php artisan permission:cache-reset</code>
                </p>
            </div>
        </div>

        <!-- Close Button -->
        <button onclick="window.close()"
                class="w-full px-4 py-2 bg-slate-600 hover:bg-slate-700 text-white text-sm font-medium rounded-lg transition-colors">
            Cerrar
        </button>
    </div>
</body>
</html>
