<?php

namespace App\Http\Middleware;

use App\Support\Demo;
use Closure;
use Illuminate\Http\Request;

/**
 * Demo-only screen gate: shows a friendly "restricted" page when the previewed
 * role would not have the screen. UI simulation - Phase 2 uses server policies.
 */
class EnsurePermission
{
    public function handle(Request $request, Closure $next, string ...$permissions)
    {
        if (! Demo::can($permissions)) {
            abort(403, 'The '.Demo::roleLabel().' role does not have access to this screen ('.implode(' / ', $permissions).').');
        }

        return $next($request);
    }
}
