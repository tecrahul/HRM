<?php

namespace App\Models;

use App\Enums\UserRole;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;
    use HasRoles;

    /**
     * Static cache to track if RBAC system is active (tables exist).
     * Using static cache to avoid repeated database schema checks.
     */
    private static ?bool $rbacSystemActive = null;

    /**
     * Static cache for permission check results within the same request.
     * Key: user_id:permission_name, Value: boolean result
     */
    private static array $permissionCheckCache = [];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'first_name',
        'middle_name',
        'last_name',
        'email',
        'password',
        'role',
        'designation_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'two_factor_secret' => 'encrypted',
            'two_factor_recovery_codes' => 'encrypted:array',
            'two_factor_enabled_at' => 'datetime',
        ];
    }

    /**
     * Append computed full_name to array/JSON output.
     *
     * @var list<string>
     */
    protected $appends = ['full_name'];

    public function getFullNameAttribute(): string
    {
        $parts = [
            trim((string) $this->first_name),
            trim((string) $this->middle_name),
            trim((string) $this->last_name),
        ];
        $parts = array_values(array_filter($parts, static fn ($p) => $p !== ''));
        $full = trim(implode(' ', $parts));

        // Fallback to legacy column if needed
        return $full !== '' ? $full : (string) ($this->attributes['name'] ?? '');
    }

    public function hasTwoFactorEnabled(): bool
    {
        return filled($this->two_factor_secret) && $this->two_factor_enabled_at !== null;
    }

    /**
     * @return list<string>
     */
    public function twoFactorRecoveryCodeHashes(): array
    {
        $hashes = $this->two_factor_recovery_codes;
        if (! is_array($hashes)) {
            return [];
        }

        return array_values(array_filter($hashes, static fn (mixed $value): bool => is_string($value) && $value !== ''));
    }

    /**
     * @param list<string> $codes
     */
    public function replaceTwoFactorRecoveryCodes(array $codes): void
    {
        $this->two_factor_recovery_codes = array_map(
            static fn (string $code): string => hash('sha256', self::normalizeTwoFactorRecoveryCode($code)),
            array_values($codes)
        );
    }

    public function consumeTwoFactorRecoveryCode(string $code): bool
    {
        $normalizedCode = self::normalizeTwoFactorRecoveryCode($code);
        if ($normalizedCode === '') {
            return false;
        }

        $targetHash = hash('sha256', $normalizedCode);
        $remainingHashes = [];
        $matched = false;

        foreach ($this->twoFactorRecoveryCodeHashes() as $hash) {
            if (! $matched && hash_equals($hash, $targetHash)) {
                $matched = true;

                continue;
            }

            $remainingHashes[] = $hash;
        }

        if ($matched) {
            $this->two_factor_recovery_codes = $remainingHashes;
        }

        return $matched;
    }

    private static function normalizeTwoFactorRecoveryCode(string $code): string
    {
        $normalized = preg_replace('/[\s-]+/', '', strtoupper(trim($code)));

        return is_string($normalized) ? $normalized : '';
    }

    /**
     * Check if RBAC system is active (Spatie permission tables exist).
     * Uses static caching to avoid repeated schema checks.
     */
    protected static function rbacActive(): bool
    {
        if (self::$rbacSystemActive !== null) {
            return self::$rbacSystemActive;
        }

        try {
            // Check if Spatie permission tables exist
            self::$rbacSystemActive = Schema::hasTable('roles')
                && Schema::hasTable('permissions')
                && Schema::hasTable('model_has_roles');
        } catch (\Throwable $e) {
            // If any error occurs during schema check, assume RBAC not active
            self::$rbacSystemActive = false;
        }

        return self::$rbacSystemActive;
    }

    /**
     * Clear static caches. Useful for testing.
     */
    public static function clearRbacCache(): void
    {
        self::$rbacSystemActive = null;
        self::$permissionCheckCache = [];
    }



    /**
     * Check if user has a specific permission.
     * Supports both RBAC (Spatie) and legacy permission config.
     *
     * @param string|\Spatie\Permission\Contracts\Permission $permission
     */
    public function hasPermission($permission, ?string $guard = null): bool
    {
        // If RBAC is active, use Spatie's implementation
        if (self::rbacActive()) {
            // Use static cache for permission checks within the same request
            $cacheKey = $this->id . ':' . (is_string($permission) ? $permission : $permission->name);

            if (isset(self::$permissionCheckCache[$cacheKey])) {
                return self::$permissionCheckCache[$cacheKey];
            }

            try {
                // Use Spatie's hasPermissionTo method from HasPermissions trait (via HasRoles)
                $result = parent::hasPermissionTo($permission, $guard);
                self::$permissionCheckCache[$cacheKey] = $result;

                return $result;
            } catch (\Throwable $e) {
                // If Spatie check fails, fall back to legacy
                self::$permissionCheckCache[$cacheKey] = false;

                return $this->hasPermissionLegacy(is_string($permission) ? $permission : $permission->name);
            }
        }

        // Fall back to legacy permission config check
        return $this->hasPermissionLegacy(is_string($permission) ? $permission : $permission->name);
    }

    /**
     * Legacy permission check using config/permissions.php mapping.
     */
    protected function hasPermissionLegacy(string $permission): bool
    {
        $permissionMap = config('permissions.map', []);
        $allowedRoles = $permissionMap[$permission] ?? [];

        if (! is_array($allowedRoles) || $allowedRoles === []) {
            return false;
        }

        return $this->hasAnyRole(array_values(array_filter($allowedRoles, 'is_string')));
    }

    /**
     * Check if user has any of the given permissions.
     * Supports both RBAC (Spatie) and legacy permission config.
     *
     * @param array|\Spatie\Permission\Contracts\Permission $permissions
     */
    public function hasAnyPermission($permissions, ?string $guard = null): bool
    {
        // If RBAC is active, use Spatie's implementation
        if (self::rbacActive()) {
            try {
                // Use Spatie's hasAnyPermission method from HasPermissions trait (via HasRoles)
                return parent::hasAnyPermission($permissions, $guard);
            } catch (\Throwable $e) {
                // Fall back to legacy if Spatie check fails
                $permissionsArray = is_array($permissions) ? $permissions : [$permissions];
                foreach ($permissionsArray as $permission) {
                    if ($this->hasPermissionLegacy(is_string($permission) ? $permission : $permission->name)) {
                        return true;
                    }
                }

                return false;
            }
        }

        // Fall back to legacy permission config check
        $permissionsArray = is_array($permissions) ? $permissions : [$permissions];

        foreach ($permissionsArray as $permission) {
            if ($this->hasPermissionLegacy(is_string($permission) ? $permission : $permission->name)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    public static function workforceRoleValues(): array
    {
        return [
            UserRole::EMPLOYEE->value,
            UserRole::HR->value,
            UserRole::FINANCE->value,
        ];
    }

    /**
     * @return list<string>
     */
    public static function nonWorkforceRoleValues(): array
    {
        return [
            UserRole::SUPER_ADMIN->value,
            UserRole::ADMIN->value,
        ];
    }

    public static function shouldTreatRoleAsEmployee(UserRole|string|null $role): bool
    {
        $roleValue = $role instanceof UserRole ? $role->value : (string) $role;

        return in_array($roleValue, self::workforceRoleValues(), true);
    }

    public static function makeEmployeeCode(int $userId): string
    {
        return sprintf('EMP-%06d', max(0, $userId));
    }

    public function isEmployeeRecord(): bool
    {
        if ($this->relationLoaded('profile')) {
            return (bool) ($this->profile?->is_employee ?? false);
        }

        $isEmployeeProfile = $this->profile()
            ->where('is_employee', true)
            ->exists();

        if ($isEmployeeProfile) {
            return true;
        }

        return self::shouldTreatRoleAsEmployee($this->role instanceof UserRole ? $this->role : (string) $this->role);
    }

    public function scopeWorkforce(Builder $query): Builder
    {
        return $query->where(function (Builder $builder): void {
            $builder
                ->whereHas('profile', function (Builder $profileQuery): void {
                    $profileQuery->where('is_employee', true);
                })
                ->orWhere(function (Builder $fallbackQuery): void {
                    $fallbackQuery
                        ->whereDoesntHave('profile')
                        ->whereIn('role', self::workforceRoleValues());
                });
        });
    }

    public function dashboardRouteName(): string
    {
        $role = $this->role instanceof UserRole
            ? $this->role
            : UserRole::tryFrom((string) $this->role);

        return match ($role) {
            UserRole::SUPER_ADMIN => 'admin.dashboard',
            UserRole::ADMIN => 'admin.dashboard',
            UserRole::HR => 'hr.dashboard',
            UserRole::FINANCE => 'finance.dashboard',
            UserRole::EMPLOYEE => 'employee.dashboard',
            default => 'employee.dashboard',
        };
    }

    public function profile(): HasOne
    {
        return $this->hasOne(UserProfile::class);
    }

    public function designation()
    {
        return $this->belongsTo(Designation::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function markedAttendances(): HasMany
    {
        return $this->hasMany(Attendance::class, 'marked_by_user_id');
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function reviewedLeaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class, 'reviewer_id');
    }

    public function payrollStructure(): HasOne
    {
        return $this->hasOne(PayrollStructure::class);
    }

    public function payrolls(): HasMany
    {
        return $this->hasMany(Payroll::class);
    }

    public function generatedPayrolls(): HasMany
    {
        return $this->hasMany(Payroll::class, 'generated_by_user_id');
    }

    public function paidPayrolls(): HasMany
    {
        return $this->hasMany(Payroll::class, 'paid_by_user_id');
    }

    public function approvedPayrolls(): HasMany
    {
        return $this->hasMany(Payroll::class, 'approved_by_user_id');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class, 'actor_user_id');
    }

    public function sentMessages(): HasMany
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    public function receivedMessages(): HasMany
    {
        return $this->hasMany(Message::class, 'receiver_id');
    }

    public function createdConversations(): HasMany
    {
        return $this->hasMany(Conversation::class, 'created_by_user_id');
    }

    public function supervisedProfiles(): HasMany
    {
        return $this->hasMany(UserProfile::class, 'supervisor_user_id');
    }

    public function directReportsQuery(): Builder
    {
        return self::query()
            ->where('role', UserRole::EMPLOYEE->value)
            ->whereHas('profile', function (Builder $query): void {
                $query->where('supervisor_user_id', $this->id);
            });
    }

    public function isSupervisor(): bool
    {
        if (! $this->hasRole(UserRole::EMPLOYEE->value)) {
            return false;
        }

        return $this->directReportsQuery()->exists();
    }
}
