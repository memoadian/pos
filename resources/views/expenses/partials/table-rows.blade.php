@forelse($expenses as $expense)
<tr class="hover:bg-slate-50">
    <td class="px-4 py-3 text-sm font-medium text-slate-900">#{{ $expense->id }}</td>
    <td class="px-4 py-3 text-sm text-slate-600 whitespace-nowrap">{{ $expense->created_at->format('d/m/Y H:i') }}</td>
    <td class="px-4 py-3 text-sm text-slate-600">{{ $expense->branch->name ?? '—' }}</td>
    <td class="px-4 py-3 text-sm text-slate-600">#{{ $expense->cash_register_id }}</td>
    <td class="px-4 py-3 text-sm text-slate-600">{{ $expense->user->name ?? $expense->user->username ?? '—' }}</td>
    <td class="px-4 py-3">
        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-700">{{ $expense->categoryLabel() }}</span>
    </td>
    <td class="px-4 py-3 text-sm text-slate-600">{{ $expense->description }}</td>
    <td class="px-4 py-3 text-sm font-semibold text-red-600 text-right tabular-nums">{{ money($expense->amount) }}</td>
    <td class="px-4 py-3">
        <div class="flex items-center justify-end gap-2">
            @can('delete', $expense)
            <button type="button"
                onclick="ConfirmModal.confirmDelete({ action: '{{ route('expenses.destroy', $expense) }}', title: 'Eliminar gasto', message: '¿Eliminar este gasto de {{ money($expense->amount) }}? El monto esperado de la caja se recalcula.' })"
                class="p-1.5 text-slate-600 hover:text-red-600 hover:bg-red-50 rounded transition-colors" title="Eliminar gasto"><i class="bi bi-trash"></i></button>
            @endcan
        </div>
    </td>
</tr>
@empty
<tr><td colspan="9" class="px-4 py-12 text-center text-slate-600"><div class="flex flex-col items-center gap-2"><i class="bi bi-cash-stack text-4xl text-slate-300"></i><p>No se encontraron gastos</p></div></td></tr>
@endforelse
