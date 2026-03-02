<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ensure user has required permission(s).
 *
 * This middleware is compatible with both:
 * - RBAC (Spatie Laravel Permission)
 * - Legacy config/permissions.php mapping
 *
 * The User model's hasAnyPermission() method handles backward compatibility automatically.
 */
class EnsureUserHasPermission
{
    /**
     * Handle an incoming request.
     *
     * @param list<string> ...$permissions
     */
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(Response::HTTP_FORBIDDEN);
        }

        // hasAnyPermission() is RBAC-aware and falls back to legacy config if needed
        if ($permissions !== [] && ! $user->hasAnyPermission($permissions)) {
            abort(Response::HTTP_FORBIDDEN, 'You do not have permission to access this resource.');
        }

        return $next($request);
    }
}

