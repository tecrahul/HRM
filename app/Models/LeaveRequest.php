<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveRequest extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_CANCELLED = 'cancelled';

    public const TYPE_CASUAL = 'casual';
    public const TYPE_SICK = 'sick';
    public const TYPE_EARNED = 'earned';
    public const TYPE_UNPAID = 'unpaid';
    public const TYPE_MATERNITY = 'maternity';
    public const TYPE_PATERNITY = 'paternity';
    public const TYPE_HALF_DAY = 'half_day';

    public const DAY_TYPE_FULL = 'full_day';
    public const DAY_TYPE_HALF = 'half_day';

    public const HALF_DAY_FIRST = 'first_half';
    public const HALF_DAY_SECOND = 'second_half';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'leave_type',
        'day_type',
        'start_date',
        'end_date',
        'total_days',
        'reason',
        'status',
        'half_day_session',
        'reviewer_id',
        'reviewed_at',
        'review_note',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'total_days' => 'decimal:2',
            'reviewed_at' => 'datetime',
        ];
    }

    /**
     * @return list<string>
     */
    public static function statuses(): array
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_APPROVED,
            self::STATUS_REJECTED,
            self::STATUS_CANCELLED,
        ];
    }

    /**
     * @return list<string>
     */
    public static function leaveTypes(): array
    {
        return [
            self::TYPE_CASUAL,
            self::TYPE_SICK,
            self::TYPE_EARNED,
            self::TYPE_UNPAID,
            self::TYPE_MATERNITY,
            self::TYPE_PATERNITY,
        ];
    }

    /**
     * @return list<string>
     */
    public static function dayTypes(): array
    {
        return [
            self::DAY_TYPE_FULL,
            self::DAY_TYPE_HALF,
        ];
    }

    /**
     * @return list<string>
     */
    public static function halfDaySessions(): array
    {
        return [
            self::HALF_DAY_FIRST,
            self::HALF_DAY_SECOND,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }
}
