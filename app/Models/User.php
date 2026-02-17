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

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
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

    public function hasRole(UserRole|string $role): bool
    {
        $targetRole = $role instanceof UserRole ? $role->value : $role;

        if ($this->role === null) {
            return false;
        }

        $currentRole = $this->role instanceof UserRole ? $this->role->value : (string) $this->role;

        return $currentRole === $targetRole;
    }

    /**
     * @param list<string> $roles
     */
    public function hasAnyRole(array $roles): bool
    {
        foreach ($roles as $role) {
            if ($this->hasRole($role)) {
                return true;
            }
        }

        return false;
    }

    public function dashboardRouteName(): string
    {
        $role = $this->role instanceof UserRole
            ? $this->role
            : UserRole::tryFrom((string) $this->role);

        return match ($role) {
            UserRole::ADMIN => 'admin.dashboard',
            UserRole::HR => 'hr.dashboard',
            UserRole::EMPLOYEE => 'employee.dashboard',
            default => 'employee.dashboard',
        };
    }

    public function profile(): HasOne
    {
        return $this->hasOne(UserProfile::class);
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
