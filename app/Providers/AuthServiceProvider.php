<?php

namespace App\Providers;

use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\Payroll;
use App\Models\User;
use App\Policies\AttendancePolicy;
use App\Policies\LeavePolicy;
use App\Policies\PayrollPolicy;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * Register all model policies here. Each policy handles its own authorization logic.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Attendance::class => AttendancePolicy::class,
        LeaveRequest::class => LeavePolicy::class,
        Payroll::class => PayrollPolicy::class,
        User::class => UserPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // CRITICAL: DO NOT add Gate::before() callback here!
        //
        // Previously, we had:
        // Gate::before(function (User $user, string $ability) {
        //     return $user->hasPermission($ability) ? true : null;
        // });
        //
        // This caused MASSIVE performance issues because:
        // 1. It runs on EVERY authorization check (including policy checks)
        // 2. Creates circular dependencies and infinite loops
        // 3. Triggers N+1 queries
        // 4. Conflicts with Spatie's permission checking
        //
        // Instead, policies handle their own authorization using:
        // - $user->hasPermission() for simple checks
        // - $user->hasAnyPermission() for multiple permissions
        // - PermissionScopeResolver::resolve() for scoped permissions
        //
        // This approach is:
        // - More explicit and maintainable
        // - Avoids performance issues
        // - Allows fine-grained control per model
        // - Works correctly with both RBAC and legacy permission systems
    }
}


