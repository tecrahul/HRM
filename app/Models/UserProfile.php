<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserProfile extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'avatar_url',
        'phone',
        'alternate_phone',
        'department',
        'branch',
        'job_title',
        'employment_type',
        'is_employee',
        'employee_code',
        'status',
        'joined_on',
        'date_of_birth',
        'gender',
        'marital_status',
        'nationality',
        'national_id',
        'work_location',
        'manager_name',
        'bank_account_name',
        'bank_account_number',
        'bank_ifsc',
        'supervisor_user_id',
        'linkedin_url',
        'address',
        'emergency_contact_name',
        'emergency_contact_phone',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'joined_on' => 'date',
            'date_of_birth' => 'date',
            'is_employee' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_user_id');
    }
}
