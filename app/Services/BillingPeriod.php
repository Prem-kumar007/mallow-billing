<?php

namespace App\Services;

use Carbon\Carbon;
use RuntimeException;

class BillingPeriod
{
    /**
     * @return array{start: Carbon, end: Carbon}
     */
    public static function firstPeriod(
        Carbon $startedAt,
        int $count,
        string $unit
    ): array {
        if ($count < 1) {
            throw new RuntimeException('Invalid billing interval.');
        }

        $start = match ($unit) {
            'day' => $startedAt->copy()->startOfDay(),
            'week' => $startedAt->copy()->startOfWeek(Carbon::MONDAY),
            'month' => $startedAt->copy()->startOfMonth(),
            'year' => $startedAt->copy()->startOfYear(),
            default => throw new RuntimeException('Invalid billing interval.'),
        };

        return [
            'start' => $start,
            'end' => self::nextEnd($start, $count, $unit),
        ];
    }

    public static function nextEnd(Carbon $periodEnd, int $count, string $unit): Carbon
    {
        if ($count < 1) {
            throw new RuntimeException('Invalid billing interval.');
        }

        $next = $periodEnd->copy();

        return match ($unit) {
            'day' => $next->addDays($count),
            'week' => $next->addWeeks($count),
            'month' => $next->addMonthsNoOverflow($count),
            'year' => $next->addYearsNoOverflow($count),
            default => throw new RuntimeException('Invalid billing interval.'),
        };
    }
}
