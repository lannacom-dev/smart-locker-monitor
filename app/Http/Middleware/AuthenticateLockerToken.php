<?php

namespace App\Http\Middleware;

use App\Models\Locker;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Authenticate a request from a physical locker device.
 *
 * The device must send its API token in the Authorization header:
 *   Authorization: Bearer <api_token>
 * OR as a query parameter:  ?api_token=<api_token>
 *
 * On success, the resolved Locker is bound into the request as `locker`.
 */
class AuthenticateLockerToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken()
            ?? $request->header('X-Locker-Token')
            ?? $request->query('api_token');

        if (! $token) {
            return response()->json(['error' => 'Missing locker token.'], 401);
        }

        $locker = Locker::where('api_token', $token)
            ->where('is_active', true)
            ->first();

        if (! $locker) {
            return response()->json(['error' => 'Invalid or inactive locker token.'], 401);
        }

        // Bind the authenticated locker into the request so the controller can use it
        $request->merge(['_locker' => $locker]);
        $request->attributes->set('authenticated_locker', $locker);

        return $next($request);
    }
}
