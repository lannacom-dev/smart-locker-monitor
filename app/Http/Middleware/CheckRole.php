<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Hierarchy-aware role middleware.
 *
 * Usage:  middleware('check.role:tenant_admin,operator')
 * Passes if the authenticated user holds ANY of the listed roles.
 *
 * Also enforces that callers cannot modify users of equal or higher level —
 * that logic lives in UserManagementService::authorize(), not here.
 */
class CheckRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (! auth()->check()) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Unauthenticated.'], 401)
                : redirect()->route('filament.admin.auth.login');
        }

        if (empty($roles)) {
            return $next($request); // no role restriction
        }

        if (auth()->user()->hasAnyRole($roles)) {
            return $next($request);
        }

        return $request->expectsJson()
            ? response()->json(['message' => 'Insufficient privileges.'], 403)
            : abort(403, 'Insufficient privileges.');
    }
}
