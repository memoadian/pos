<?php

namespace App\Http\Middleware;

use App\Services\BranchContextService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class ShareBranchContext
{
    public function __construct(private BranchContextService $branchContext)
    {
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check()) {
            View::share('currentBranch', $this->branchContext->current());
            View::share('branchContext', $this->branchContext);
        }

        return $next($request);
    }
}
