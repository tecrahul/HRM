<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * @param list<string> ...$roles
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(Response::HTTP_FORBIDDEN);
        }

        if ($roles !== [] && ! $user->hasAnyRole($roles)) {
            abort(Response::HTTP_FORBIDDEN, 'You do not have access to this resource.');
        }

        return $next($request);
    }
}
