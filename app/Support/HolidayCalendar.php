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

        $holidayRanges = Holiday::query()
            ->withinDateRange($startDate->toDateString(), $endDate->toDateString())
            ->where('is_active', true)
            ->when(! $includeOptional, function ($query): void {
                $query->where('is_optional', false);
            })
            ->where(function ($query) use ($branchId): void {
                $query->whereNull('branch_id');

                if ($branchId !== null) {
                    $query->orWhere('branch_id', $branchId);
                }
            })
            ->get(['holiday_date', 'end_date']);

        $dates = $holidayRanges
            ->flatMap(function (Holiday $holiday): array {
                $start = $holiday->holiday_date?->copy();
                if ($start === null) {
                    return [];
                }

                $end = $holiday->end_date?->copy();
                if ($end === null || $end->lt($start)) {
                    $end = $start->copy();
                }

                $days = [];
                $cursor = $start->copy();
                while ($cursor->lte($end)) {
                    $days[] = $cursor->toDateString();
                    $cursor->addDay();
                }

                return $days;
            })
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
