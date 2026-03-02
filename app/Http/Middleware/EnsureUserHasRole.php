<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ensure user has required role(s).
 *
 * This middleware is compatible with both:
 * - RBAC (Spatie Laravel Permission)
 * - Legacy role column
 *
 * The User model's hasAnyRole() method handles backward compatibility automatically.
 */
class EnsureUserHasRole
{
    /**
     * Handle an incoming request.
     *
     * @param list<string> ...$roles
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(Response::HTTP_FORBIDDEN);
        }

        // hasAnyRole() is RBAC-aware and falls back to legacy role column if needed
        if ($roles !== [] && ! $user->hasAnyRole($roles)) {
            abort(Response::HTTP_FORBIDDEN, 'You do not have access to this resource.');
        }

        return $next($request);
    }
}
