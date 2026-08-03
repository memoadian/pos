@if(!$branchContext->canSwitch())
    <div class="flex items-center gap-2 text-sm">
        <span class="text-slate-500">Sucursal:</span>
        <span class="font-medium text-slate-900">{{ $currentBranch->name ?? 'Sin sucursal' }}</span>
    </div>
@else
    <div class="relative">
        <button type="button" id="branchSwitcherBtn"
                class="flex items-center gap-2 text-sm px-3 py-1.5 border border-slate-200 rounded-lg hover:bg-slate-50 transition-colors">
            <i class="bi bi-building text-slate-400"></i>
            <span class="font-medium text-slate-900">{{ $currentBranch->name ?? 'Selecciona sucursal' }}</span>
            <i class="bi bi-chevron-down text-slate-400 text-xs"></i>
        </button>

        <div id="branchSwitcherMenu"
             class="hidden absolute left-0 mt-2 w-64 bg-white border border-slate-200 rounded-lg shadow-lg z-50 py-1 max-h-80 overflow-y-auto">
            @forelse($branchContext->availableBranches() as $branch)
                <form method="POST" action="{{ route('branch-context.switch') }}">
                    @csrf
                    <input type="hidden" name="branch_id" value="{{ $branch->id }}">
                    <button type="submit"
                            class="w-full flex items-center justify-between gap-2 px-4 py-2 text-sm text-left hover:bg-slate-50 transition-colors {{ $currentBranch?->id === $branch->id ? 'text-cyan-700 font-medium' : 'text-slate-700' }}">
                        <span>{{ $branch->name }}</span>
                        @if($currentBranch?->id === $branch->id)
                            <i class="bi bi-check-lg text-cyan-600"></i>
                        @endif
                    </button>
                </form>
            @empty
                <p class="px-4 py-2 text-sm text-slate-500">No tienes sucursales asignadas.</p>
            @endforelse
        </div>
    </div>
@endif
