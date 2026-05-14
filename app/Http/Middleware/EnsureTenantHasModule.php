<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantHasModule
{
    /**
     * Gate route access by the tenant's enabled modules.
     *
     * Usage:  Route::middleware('module:eatery')
     *
     * Super-admin requests bypass the check — they manage every tenant.
     */
    public function handle(Request $request, Closure $next, string $module): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        $tenant = $user->tenant;

        if (! $tenant || ! $tenant->hasModule($module)) {
            return response()->json([
                'success' => false,
                'message' => "Your store does not have the '{$module}' module enabled.",
                'module'  => $module,
            ], 403);
        }

        return $next($request);
    }
}
