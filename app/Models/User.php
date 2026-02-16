<?php

namespace App\Models;

use App\Enums\UserRole;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
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
        ];
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
}
