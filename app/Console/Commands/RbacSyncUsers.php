<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;

class RbacSyncUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rbac:sync-users
                            {--force : Force sync even if users already have roles}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync all users from legacy role column to Spatie RBAC roles';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Syncing users from legacy role column to RBAC roles...');
        $this->newLine();

        $force = $this->option('force');

        $users = User::all();

        if ($users->isEmpty()) {
            $this->warn('No users found in the database.');

            return self::SUCCESS;
        }

        $this->info("Found {$users->count()} users to process.");
        $this->newLine();

        $syncedCount = 0;
        $skippedCount = 0;
        $errorCount = 0;

        $progressBar = $this->output->createProgressBar($users->count());
        $progressBar->start();

        foreach ($users as $user) {
            $progressBar->advance();

            // Skip if user has no role
            if (! $user->role) {
                $skippedCount++;
                continue;
            }

            // Get role value (handle both enum and string)
            $roleValue = $user->role instanceof UserRole
                ? $user->role->value
                : (string) $user->role;

            try {
                // Check if role exists in Spatie
                $role = Role::findByName($roleValue);

                // Skip if user already has this role (unless force flag is set)
                if (! $force && $user->hasRole($roleValue)) {
                    $skippedCount++;
                    continue;
                }

                // Assign role to user
                if ($force) {
                    // Force: sync roles (remove old, add new)
                    $user->syncRoles([$role]);
                } else {
                    // Normal: assign role if not already assigned
                    $user->assignRole($role);
                }

                $syncedCount++;
            } catch (\Throwable $e) {
                $errorCount++;
                $this->newLine();
                $this->error("  Failed to sync user #{$user->id} ({$user->email}): {$e->getMessage()}");
            }
        }

        $progressBar->finish();
        $this->newLine(2);

        // Summary
        $this->info('Sync Summary:');
        $this->table(
            ['Status', 'Count'],
            [
                ['Synced', $syncedCount],
                ['Skipped', $skippedCount],
                ['Errors', $errorCount],
                ['Total', $users->count()],
            ]
        );

        if ($syncedCount > 0) {
            $this->info("✓ Successfully synced {$syncedCount} users to RBAC roles!");
        }

        if ($errorCount > 0) {
            $this->warn("⚠ {$errorCount} errors occurred during sync. Check logs for details.");

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}

