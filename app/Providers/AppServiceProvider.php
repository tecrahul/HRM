<?php

namespace App\Providers;

use App\Services\MailConfigurationManager;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Str;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(MailConfigurationManager::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(MailConfigurationManager $mailConfigurationManager): void
    {
        // Apply any active SMTP configuration from DB at runtime
        $mailConfigurationManager->applyRuntimeConfiguration();

        // Security: Rate limit sensitive auth flows
        RateLimiter::for('login', function (Request $request) {
            $email = Str::lower((string) $request->input('email'));
            return Limit::perMinute(5)->by($email.'|'.$request->ip());
        });

        RateLimiter::for('two-factor', function (Request $request) {
            // Bind to pending 2FA session when available, else IP-based
            $pendingId = (string) $request->session()->get('auth.two_factor.user_id', 'guest');
            return Limit::perMinute(5)->by($pendingId.'|'.$request->ip());
        });
    }
}
