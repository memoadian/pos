@if(session('success'))
<div class="bg-emerald-50 border border-emerald-200 rounded-lg p-4 flex items-center gap-3 mb-6">
    <i class="bi bi-check-circle-fill text-emerald-600 text-lg flex-shrink-0"></i>
    <p class="text-sm text-emerald-800">{{ session('success') }}</p>
</div>
@endif

@if(session('error'))
<div class="bg-red-50 border border-red-200 rounded-lg p-4 flex items-center gap-3 mb-6">
    <i class="bi bi-exclamation-circle-fill text-red-600 text-lg flex-shrink-0"></i>
    <p class="text-sm text-red-800">{{ session('error') }}</p>
</div>
@endif

@if($errors->any())
<div class="bg-amber-50 border border-amber-200 rounded-lg p-4 mb-6">
    <div class="flex items-center gap-3 mb-3">
        <i class="bi bi-exclamation-triangle-fill text-amber-600 text-lg flex-shrink-0"></i>
        <p class="text-sm font-medium text-amber-900">Hay errores en el formulario:</p>
    </div>
    <ul class="ml-8 space-y-1">
        @foreach($errors->all() as $error)
        <li class="list-disc text-sm text-amber-800">{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif
