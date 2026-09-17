<?php

namespace Tests\Unit;

use App\Services\LeaveCalculator;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class LeaveCalculatorTest extends TestCase
{
    public function test_same_day_weekday_counts_as_one_day(): void
    {
        $monday = Carbon::now()->next(Carbon::MONDAY);

        $this->assertSame(1.0, LeaveCalculator::workingDaysBetween($monday, $monday));
    }

    public function test_full_week_monday_to_friday_counts_five_days(): void
    {
        $monday = Carbon::now()->next(Carbon::MONDAY);
        $friday = $monday->copy()->addDays(4);

        $this->assertSame(5.0, LeaveCalculator::workingDaysBetween($monday, $friday));
    }

    public function test_range_spanning_a_weekend_excludes_weekend_days(): void
    {
        $friday = Carbon::now()->next(Carbon::MONDAY)->addDays(4);
        $nextMonday = $friday->copy()->addDays(3);

        $this->assertSame(2.0, LeaveCalculator::workingDaysBetween($friday, $nextMonday));
    }

    public function test_weekend_only_range_returns_zero_days(): void
    {
        $saturday = Carbon::now()->next(Carbon::SATURDAY);
        $sunday = $saturday->copy()->addDay();

        $this->assertSame(0.0, LeaveCalculator::workingDaysBetween($saturday, $sunday));
    }

    public function test_half_day_returns_half_regardless_of_range(): void
    {
        $monday = Carbon::now()->next(Carbon::MONDAY);

        $this->assertSame(0.5, LeaveCalculator::workingDaysBetween($monday, $monday, true));
    }
}
