<?php

namespace App\Support;

use App\Models\CompanySetting;
use Carbon\Carbon;

class FinancialYear
{
    private const DEFAULT_START_MONTH = 4;

    private static ?int $cachedStartMonth = null;

    public static function startMonth(): int
    {
        if (self::$cachedStartMonth !== null) {
            return self::$cachedStartMonth;
        }

        $value = CompanySetting::query()->value('financial_year_start_month');
        $month = (int) ($value ?: self::DEFAULT_START_MONTH);

        if ($month < 1 || $month > 12) {
            $month = self::DEFAULT_START_MONTH;
        }

        self::$cachedStartMonth = $month;

        return $month;
    }

    /**
     * @return array{start: Carbon, end: Carbon}
     */
    public static function rangeForStartYear(int $startYear): array
    {
        $start = Carbon::create($startYear, self::startMonth(), 1)->startOfDay();
        $end = $start->copy()->addYear()->subDay()->endOfDay();

        return [
            'start' => $start,
            'end' => $end,
        ];
    }

    public static function currentStartYear(?Carbon $date = null): int
    {
        $resolvedDate = $date?->copy() ?? now();
        $year = (int) $resolvedDate->format('Y');

        return $resolvedDate->month >= self::startMonth()
            ? $year
            : $year - 1;
    }

    public static function label(int $startYear): string
    {
        return "FY {$startYear}-" . ($startYear + 1);
    }

    public static function monthLabel(int $month): string
    {
        if ($month < 1 || $month > 12) {
            $month = self::DEFAULT_START_MONTH;
        }

        return Carbon::create(2024, $month, 1)->format('F');
    }
}
