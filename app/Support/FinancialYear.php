<?php

namespace App\Support;

use App\Models\CompanySetting;
use Carbon\Carbon;

class FinancialYear
{
    private const DEFAULT_START_MONTH = 4;
    private const DEFAULT_START_DAY = 1;

    private static ?int $cachedStartMonth = null;
    private static ?int $cachedStartDay = null;
    private static ?int $cachedEndMonth = null;
    private static ?int $cachedEndDay = null;

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

    public static function startDay(): int
    {
        if (self::$cachedStartDay !== null) {
            return self::$cachedStartDay;
        }

        $value = CompanySetting::query()->value('financial_year_start_day');
        $day = (int) ($value ?: self::DEFAULT_START_DAY);

        if ($day < 1 || $day > 31) {
            $day = self::DEFAULT_START_DAY;
        }

        self::$cachedStartDay = $day;

        return $day;
    }

    public static function endMonth(): int
    {
        self::ensureEndBoundary();

        return self::$cachedEndMonth ?? self::startMonth();
    }

    public static function endDay(): int
    {
        self::ensureEndBoundary();

        return self::$cachedEndDay ?? self::startDay();
    }

    /**
     * @return array{start: Carbon, end: Carbon}
     */
    public static function rangeForStartYear(int $startYear): array
    {
        $startMonth = self::startMonth();
        $startDay = self::startDay();
        $start = Carbon::create($startYear, $startMonth, $startDay)->startOfDay();

        $endMonth = self::endMonth();
        $endDay = self::endDay();
        $endYear = $startYear;

        if ($endMonth < $startMonth || ($endMonth === $startMonth && $endDay < $startDay)) {
            $endYear++;
        }

        $end = Carbon::create($endYear, $endMonth, $endDay)->endOfDay();

        return [
            'start' => $start,
            'end' => $end,
        ];
    }

    public static function currentStartYear(?Carbon $date = null): int
    {
        $resolvedDate = $date?->copy() ?? now();
        $year = (int) $resolvedDate->format('Y');
        $startMonth = self::startMonth();

        return $resolvedDate->month > $startMonth
            ? $year
            : ($resolvedDate->month === $startMonth && $resolvedDate->day >= self::startDay() ? $year : $year - 1);
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

    private static function ensureEndBoundary(): void
    {
        if (self::$cachedEndMonth !== null && self::$cachedEndDay !== null) {
            return;
        }

        $record = CompanySetting::query()->first([
            'financial_year_end_month',
            'financial_year_end_day',
        ]);

        $month = (int) ($record?->financial_year_end_month ?? 0);
        $day = (int) ($record?->financial_year_end_day ?? 0);

        if ($month >= 1 && $month <= 12 && $day >= 1 && $day <= 31 && checkdate($month, $day, 2024)) {
            self::$cachedEndMonth = $month;
            self::$cachedEndDay = $day;

            return;
        }

        $start = Carbon::create(2024, self::startMonth(), self::startDay());
        $end = $start->copy()->addYear()->subDay();
        self::$cachedEndMonth = (int) $end->format('n');
        self::$cachedEndDay = (int) $end->format('j');
    }
}
