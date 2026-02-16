<?php

namespace App\Http\Middleware;

use App\Support\NotificationCenter;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SyncRoleNotifications
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $routeName = $request->route()?->getName();
        if ($routeName === 'settings.company.logo') {
            return $next($request);
        }

        NotificationCenter::syncFor($request->user());

        return $next($request);
    }
}
