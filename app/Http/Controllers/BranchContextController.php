<?php

namespace App\Http\Controllers;

use App\Services\BranchContextService;
use Illuminate\Http\Request;

class BranchContextController extends Controller
{
    /**
     * Switch the authenticated user's current working branch.
     */
    public function switch(Request $request, BranchContextService $branchContext)
    {
        $request->validate([
            'branch_id' => 'required|integer|exists:branches,id',
        ]);

        if (!$branchContext->canSwitch()) {
            abort(403);
        }

        $branch = $branchContext->availableBranches()->firstWhere('id', (int) $request->branch_id);
        if (!$branch) {
            abort(403, 'No tienes acceso a esa sucursal.');
        }

        $branchContext->switchTo($branch);

        return back()->with('success', "Sucursal activa: {$branch->name}");
    }
}
