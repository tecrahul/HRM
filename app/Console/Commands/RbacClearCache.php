<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Support\PermissionScopeResolver;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;

class RbacClearCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rbac:clear-cache';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear all RBAC-related caches (Spatie permissions, User model, PermissionScopeResolver)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Clearing RBAC caches...');

        // Clear Spatie permission cache
        $this->info('  Clearing Spatie permission cache...');
        try {
            Artisan::call('permission:cache-reset');
            $this->info('    ✓ Spatie permission cache cleared');
        } catch (\Throwable $e) {
            $this->warn('    ⚠ Failed to clear Spatie cache: ' . $e->getMessage());
        }

        // Clear User model static caches
        $this->info('  Clearing User model static caches...');
        try {
            User::clearRbacCache();
            $this->info('    ✓ User model RBAC cache cleared');
        } catch (\Throwable $e) {
            $this->warn('    ⚠ Failed to clear User cache: ' . $e->getMessage());
        }

        // Clear PermissionScopeResolver static caches
        $this->info('  Clearing PermissionScopeResolver static caches...');
        try {
            PermissionScopeResolver::clearCache();
            $this->info('    ✓ PermissionScopeResolver cache cleared');
        } catch (\Throwable $e) {
            $this->warn('    ⚠ Failed to clear PermissionScopeResolver cache: ' . $e->getMessage());
        }

        // Clear application cache (optional, but recommended)
        $this->info('  Clearing application cache...');
        try {
            Cache::flush();
            $this->info('    ✓ Application cache cleared');
        } catch (\Throwable $e) {
            $this->warn('    ⚠ Failed to clear application cache: ' . $e->getMessage());
        }

        $this->newLine();
        $this->info('✓ All RBAC caches cleared successfully!');

        return self::SUCCESS;
    }
}

