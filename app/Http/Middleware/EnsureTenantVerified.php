<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        if (! $user->tenant || ! $user->tenant->isVerified()) {
            return response()->json([
                'success' => false,
                'message' => 'Your store account is pending verification. Please check your email.',
            ], 403);
        }

        return $next($request);
    }
}
