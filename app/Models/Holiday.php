<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Holiday extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'holiday_date',
        'end_date',
        'branch_id',
        'holiday_type',
        'is_active',
        'is_optional',
        'description',
        'created_by_user_id',
        'updated_by_user_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'holiday_date' => 'date',
            'end_date' => 'date',
            'is_active' => 'bool',
            'is_optional' => 'bool',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }

    public function scopeWithinDateRange(Builder $query, string $startDate, string $endDate): Builder
    {
        return $query->where(function (Builder $rangeQuery) use ($startDate, $endDate): void {
            $rangeQuery
                ->whereBetween('holiday_date', [$startDate, $endDate])
                ->orWhere(function (Builder $multiDayQuery) use ($startDate, $endDate): void {
                    $multiDayQuery
                        ->whereNotNull('end_date')
                        ->whereDate('holiday_date', '<=', $endDate)
                        ->whereDate('end_date', '>=', $startDate);
                });
        });
    }
}
