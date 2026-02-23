<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceMonthLock extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'attendance_month',
        'locked_by_user_id',
        'locked_at',
        'unlocked_by_user_id',
        'unlocked_at',
        'unlock_reason',
        'metadata',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'attendance_month' => 'date',
            'locked_at' => 'datetime',
            'unlocked_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function lockedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_by_user_id');
    }

    public function unlockedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'unlocked_by_user_id');
    }
}

