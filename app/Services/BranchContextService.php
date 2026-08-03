<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Support\Collection;

class BranchContextService
{
    /**
     * Whether the user can switch their working branch (Admin/Manager).
     * Vendedor-type users are fixed to their single home branch.
     */
    public function canSwitch(?User $user = null): bool
    {
        $user ??= auth()->user();
        return $user && $user->hasRole(['Admin', 'Manager']);
    }

    /**
     * Branches this user is permitted to pick as their current working branch.
     */
    public function availableBranches(?User $user = null): Collection
    {
        $user ??= auth()->user();
        if (!$user) {
            return collect();
        }

        if ($user->hasRole('Admin')) {
            return Branch::where('is_active', true)->orderBy('name')->get();
        }

        if ($user->hasRole('Manager')) {
            return $user->managedBranches()->where('is_active', true)->orderBy('name')->get();
        }

        return $user->branch && $user->branch->is_active ? collect([$user->branch]) : collect();
    }

    /**
     * Resolve the user's current working branch, self-healing to a valid
     * available branch and persisting the result if the stored value is
     * missing, stale, or no longer permitted.
     */
    public function current(?User $user = null): ?Branch
    {
        $user ??= auth()->user();
        if (!$user) {
            return null;
        }

        if (!$this->canSwitch($user)) {
            return $user->branch;
        }

        $available = $this->availableBranches($user);
        $selected = $available->firstWhere('id', $user->current_branch_id);
        if ($selected) {
            return $selected;
        }

        $fallback = $available->first();
        if ($fallback && $user->current_branch_id !== $fallback->id) {
            $this->persist($user, $fallback->id);
        }

        return $fallback;
    }

    public function currentId(?User $user = null): ?int
    {
        return $this->current($user)?->id;
    }

    /**
     * Switch the user's current working branch, persisted across sessions.
     */
    public function switchTo(Branch $branch, ?User $user = null): void
    {
        $user ??= auth()->user();
        if (!$this->availableBranches($user)->contains('id', $branch->id)) {
            abort(403, 'No tienes acceso a esa sucursal.');
        }
        $this->persist($user, $branch->id);
    }

    private function persist(User $user, int $branchId): void
    {
        $user->forceFill(['current_branch_id' => $branchId])->save();
    }
}
