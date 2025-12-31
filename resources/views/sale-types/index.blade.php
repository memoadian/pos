@extends('layouts.app')
@section('title', 'Tipos de Venta')
@section('content')
<div class="space-y-6">
    @include('components.alerts')
    <div class="flex items-center justify-between">
        <div><h1 class="text-xl font-semibold text-slate-900">Tipos de Venta</h1><p class="text-sm text-slate-500 mt-1">Administra los tipos de venta del sistema</p></div>
        @can('create', App\Models\SaleType::class)
        <a href="{{ route('sale-types.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-lg transition-colors"><i class="bi bi-plus-lg"></i><span>Nuevo Tipo</span></a>
        @endcan
    </div>
    <div class="bg-white rounded-lg border border-slate-200 p-4"><div class="flex gap-3">
        <div class="flex-1"><input type="text" id="searchInput" placeholder="Buscar..." class="w-full px-4 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none transition"></div>
        <select id="activeFilter" class="px-3 py-2 text-sm border border-slate-300 rounded-lg"><option value="">Todos</option><option value="1">Activos</option><option value="0">Inactivos</option></select>
    </div></div>
    <div class="bg-white rounded-lg border border-slate-200 overflow-hidden"><table class="w-full"><thead class="bg-slate-50 border-b border-slate-200"><tr><th class="px-4 py-3 text-left text-xs font-medium text-slate-600 uppercase">#</th><th class="px-4 py-3 text-left text-xs font-medium text-slate-600 uppercase">Nombre</th><th class="px-4 py-3 text-left text-xs font-medium text-slate-600 uppercase">Código</th><th class="px-4 py-3 text-left text-xs font-medium text-slate-600 uppercase">Productos</th><th class="px-4 py-3 text-left text-xs font-medium text-slate-600 uppercase">Estado</th><th class="px-4 py-3 text-right text-xs font-medium text-slate-600 uppercase">Acciones</th></tr></thead><tbody id="typesTable" class="divide-y divide-slate-200">@include('sale-types.partials.table-rows')</tbody></table></div>
    @if($saleTypes->hasPages())<div class="flex justify-center">{{ $saleTypes->links() }}</div>@endif
</div>
@endsection
@section('scripts')
<script>
const filters={search:document.getElementById('searchInput'),active:document.getElementById('activeFilter')};
let debounce;
filters.search.addEventListener('input',()=>{clearTimeout(debounce);debounce=setTimeout(filter,300);});
filters.active.addEventListener('change',filter);
function filter(){const url=new URL('{{route("sale-types.index")}}');if(filters.search.value)url.searchParams.append('search',filters.search.value);if(filters.active.value)url.searchParams.append('is_active',filters.active.value);fetch(url,{headers:{'X-Requested-With':'XMLHttpRequest'}}).then(r=>r.text()).then(html=>{document.getElementById('typesTable').innerHTML=html;});}
function confirmDelete(id){if(confirm('¿Desactivar este tipo de venta?')){const form=document.createElement('form');form.method='POST';form.action=`/sale-types/${id}`;form.innerHTML=`<input type="hidden" name="_token" value="${document.querySelector('meta[name="csrf-token"]').content}"><input type="hidden" name="_method" value="DELETE">`;document.body.appendChild(form);form.submit();}}
</script>
@endsection
