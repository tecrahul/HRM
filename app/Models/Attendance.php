<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    public const STATUS_PRESENT = 'present';
    public const STATUS_ABSENT = 'absent';
    public const STATUS_LATE = 'late';
    public const STATUS_HALF_DAY = 'half_day';
    public const STATUS_REMOTE = 'remote';
    public const STATUS_ON_LEAVE = 'on_leave';
    public const APPROVAL_PENDING = 'pending';
    public const APPROVAL_APPROVED = 'approved';
    public const APPROVAL_REJECTED = 'rejected';
    public const APPROVAL_LOCKED = 'locked';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'attendance_date',
        'status',
        'approval_status',
        'check_in_at',
        'check_out_at',
        'work_minutes',
        'notes',
        'approval_note',
        'marked_by_user_id',
        'approved_by_user_id',
        'approved_at',
        'rejected_by_user_id',
        'rejected_at',
        'correction_requested_by_user_id',
        'correction_requested_at',
        'correction_reason',
        'requested_check_in_at',
        'requested_check_out_at',
        'requested_work_minutes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'attendance_date' => 'date',
            'check_in_at' => 'datetime',
            'check_out_at' => 'datetime',
            'work_minutes' => 'integer',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'correction_requested_at' => 'datetime',
            'requested_check_in_at' => 'datetime',
            'requested_check_out_at' => 'datetime',
            'requested_work_minutes' => 'integer',
        ];
    }

    /**
     * @return list<string>
     */
    public static function statuses(): array
    {
        return [
            self::STATUS_PRESENT,
            self::STATUS_ABSENT,
            self::STATUS_LATE,
            self::STATUS_HALF_DAY,
            self::STATUS_REMOTE,
            self::STATUS_ON_LEAVE,
        ];
    }

    /**
     * @return list<string>
     */
    public static function approvalStatuses(): array
    {
        return [
            self::APPROVAL_PENDING,
            self::APPROVAL_APPROVED,
            self::APPROVAL_REJECTED,
            self::APPROVAL_LOCKED,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function markedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'marked_by_user_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    public function rejectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by_user_id');
    }

    public function correctionRequestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'correction_requested_by_user_id');
    }
}
