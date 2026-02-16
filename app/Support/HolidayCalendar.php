<?php

namespace App\Support;

use App\Models\Branch;
use App\Models\Holiday;
use Carbon\Carbon;

class HolidayCalendar
{
    /**
     * @return array<string, bool>
     */
    public static function dateMap(
        Carbon $startDate,
        Carbon $endDate,
        ?string $branchName = null,
        bool $includeOptional = false
    ): array {
        $branchId = self::resolveBranchId($branchName);

        $dates = Holiday::query()
            ->withinDateRange($startDate->toDateString(), $endDate->toDateString())
            ->when(! $includeOptional, function ($query): void {
                $query->where('is_optional', false);
            })
            ->where(function ($query) use ($branchId): void {
                $query->whereNull('branch_id');

                if ($branchId !== null) {
                    $query->orWhere('branch_id', $branchId);
                }
            })
            ->pluck('holiday_date')
            ->map(fn ($value): string => Carbon::parse((string) $value)->toDateString())
            ->unique()
            ->values()
            ->all();

        /** @var array<string, bool> $result */
        $result = [];
        foreach ($dates as $date) {
            $result[$date] = true;
        }

        return $result;
    }

    private static function resolveBranchId(?string $branchName): ?int
    {
        if (blank($branchName)) {
            return null;
        }

        $name = trim((string) $branchName);

        if ($name === '') {
            return null;
        }

        $branch = Branch::query()
            ->whereRaw('LOWER(name) = ?', [strtolower($name)])
            ->first(['id']);

        return $branch ? (int) $branch->id : null;
    }
}
