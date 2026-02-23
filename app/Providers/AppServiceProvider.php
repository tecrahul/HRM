<?php

namespace App\Providers;

use App\Services\MailConfigurationManager;
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
        $mailConfigurationManager->applyRuntimeConfiguration();
    }
}
