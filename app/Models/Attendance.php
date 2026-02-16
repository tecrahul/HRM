<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    public const STATUS_PRESENT = 'present';
    public const STATUS_ABSENT = 'absent';
    public const STATUS_HALF_DAY = 'half_day';
    public const STATUS_REMOTE = 'remote';
    public const STATUS_ON_LEAVE = 'on_leave';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'attendance_date',
        'status',
        'check_in_at',
        'check_out_at',
        'work_minutes',
        'notes',
        'marked_by_user_id',
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
            self::STATUS_HALF_DAY,
            self::STATUS_REMOTE,
            self::STATUS_ON_LEAVE,
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
}
