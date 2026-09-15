@extends('layouts.app')
@section('title', 'Mínimos de Mayoreo')
@section('content')
<div class="space-y-6">
    @include('components.alerts')
    <div class="flex items-center gap-3">
        <a href="{{ route('products.index') }}" class="p-2 hover:bg-slate-100 rounded-lg transition-colors"><i class="bi bi-arrow-left text-slate-600"></i></a>
        <div>
            <h1 class="text-xl font-semibold text-slate-900">Mínimos de Mayoreo / Súper Mayoreo</h1>
            <p class="text-sm text-slate-500 mt-1">Edita, producto por producto, la cantidad mínima de piezas para activar cada nivel de precio.</p>
        </div>
    </div>

    <div class="bg-white rounded-lg border border-slate-200 p-4">
        <div class="flex flex-col md:flex-row gap-3">
            <div class="flex-1"><div class="relative"><span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400"><i class="bi bi-search"></i></span><input type="text" id="searchInput" value="{{ request('search') }}" placeholder="Buscar por nombre o código..." class="w-full pl-10 pr-4 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none transition"></div></div>
            <select id="departmentFilter" class="w-full md:w-48 px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none transition"><option value="">Todos los departamentos</option>@foreach($departments as $dept)<option value="{{ $dept->id }}" @selected((int) request('department') === $dept->id)>{{ $dept->name }}</option>@endforeach</select>
            <select id="perPageFilter" class="w-full md:w-40 px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none transition" title="Productos por página">
                @foreach(App\Http\Controllers\ProductController::PER_PAGE_OPTIONS as $option)
                <option value="{{ $option }}" @selected($perPage === $option)>Mostrar {{ $option }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <form method="POST" action="{{ route('products.minimums.update') }}" id="minimumsForm">
        @csrf
        @method('PUT')
        <input type="hidden" name="search" id="searchHidden" value="{{ request('search') }}">
        <input type="hidden" name="department" id="departmentHidden" value="{{ request('department') }}">
        <input type="hidden" name="per_page" id="perPageHidden" value="{{ $perPage }}">

        <div class="bg-white rounded-lg border border-slate-200 overflow-hidden">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 px-4 py-3 border-b border-slate-200 bg-slate-50">
                <p class="text-xs text-slate-500">Solo se guardan los productos mostrados en esta página. Guarda antes de cambiar de página o de filtro.</p>
                <button type="submit" class="px-4 py-2 bg-cyan-600 hover:bg-cyan-700 text-white text-sm font-medium rounded-lg transition-colors whitespace-nowrap self-start sm:self-auto">
                    <i class="bi bi-check-lg"></i> Guardar cambios
                </button>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-slate-50 border-b border-slate-200">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-600 uppercase tracking-wider">Producto</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-600 uppercase tracking-wider">Precio Mayoreo</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-600 uppercase tracking-wider">Mínimo Mayoreo</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-600 uppercase tracking-wider">Precio Súper Mayoreo</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-slate-600 uppercase tracking-wider">Mínimo Súper Mayoreo</th>
                        </tr>
                    </thead>
                    <tbody id="minimumsTable" class="divide-y divide-slate-200">@include('products.partials.minimum-rows')</tbody>
                </table>
            </div>
        </div>
    </form>
    <div class="flex justify-center" id="minimumsPagination">@if($products->hasPages()){{ $products->links() }}@endif</div>
</div>
@endsection

@section('scripts')
<script>
const searchInput = document.getElementById('searchInput');
const departmentFilter = document.getElementById('departmentFilter');
const perPageFilter = document.getElementById('perPageFilter');
const tableBody = document.getElementById('minimumsTable');
const pagination = document.getElementById('minimumsPagination');
const searchHidden = document.getElementById('searchHidden');
const departmentHidden = document.getElementById('departmentHidden');
const perPageHidden = document.getElementById('perPageHidden');
let debounceTimer;

searchInput.addEventListener('input', () => { clearTimeout(debounceTimer); debounceTimer = setTimeout(filterProducts, 300); });
departmentFilter.addEventListener('change', filterProducts);
perPageFilter.addEventListener('change', filterProducts);

function filterProducts() {
    const url = new URL('{{ route("products.minimums.edit") }}');
    if (searchInput.value) url.searchParams.append('search', searchInput.value);
    if (departmentFilter.value) url.searchParams.append('department', departmentFilter.value);
    url.searchParams.append('per_page', perPageFilter.value);

    fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(r => r.json())
        .then(data => {
            tableBody.innerHTML = data.rows;
            pagination.innerHTML = data.pagination;
            // Los campos ocultos del form de guardado se sincronizan con el
            // filtro actual, para que el redirect tras guardar vuelva aqui mismo.
            searchHidden.value = searchInput.value;
            departmentHidden.value = departmentFilter.value;
            perPageHidden.value = perPageFilter.value;
            history.replaceState(null, '', url.search);
        })
        .catch(e => console.error('Error:', e));
}
</script>
@endsection
