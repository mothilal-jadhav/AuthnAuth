<?php

namespace App\Services;

use Carbon\Carbon;
use Carbon\CarbonPeriod;

class LeaveCalculator
{
    /**
     * Working days (Mon-Fri) between two dates, inclusive. Public holidays
     * are not accounted for — an explicit non-goal of this phase. A
     * half-day request always counts as 0.5 regardless of the range, since
     * it's only ever valid for a single-day request (enforced by the
     * caller).
     */
    public static function workingDaysBetween(Carbon|string $start, Carbon|string $end, bool $isHalfDay = false): float
    {
        $start = Carbon::parse($start)->startOfDay();
        $end = Carbon::parse($end)->startOfDay();

        $days = 0;

        foreach (CarbonPeriod::create($start, $end) as $date) {
            if (! $date->isWeekend()) {
                $days++;
            }
        }

        if ($isHalfDay && $days > 0) {
            return 0.5;
        }

        return (float) $days;
    }
}
