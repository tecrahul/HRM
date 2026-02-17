<?php

namespace App\Http\Middleware;

use App\Models\CompanySetting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAuthFeatureEnabled
{
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        $enabled = match ($feature) {
            'signup' => CompanySetting::signupEnabled(),
            'password-reset' => CompanySetting::passwordResetEnabled(),
            'two-factor' => CompanySetting::twoFactorEnabled(),
            default => false,
        };

        if (! $enabled) {
            abort(Response::HTTP_NOT_FOUND);
        }

        return $next($request);
    }
}
