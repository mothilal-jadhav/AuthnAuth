<?php

namespace Tests\Unit;

use App\Services\AttendanceCalculator;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AttendanceCalculatorTest extends TestCase
{
    /**
     * @return array{start_time: string, end_time: string, grace_minutes: int}
     */
    private function shift(string $start = '09:00', string $end = '18:00', int $grace = 15): array
    {
        return ['start_time' => $start, 'end_time' => $end, 'grace_minutes' => $grace];
    }

    public function test_clocking_in_within_grace_period_is_present(): void
    {
        $clockIn = Carbon::today()->setTime(9, 15);

        $this->assertSame('present', AttendanceCalculator::statusForClockIn($this->shift(), $clockIn));
    }

    public function test_clocking_in_after_grace_period_is_late(): void
    {
        $clockIn = Carbon::today()->setTime(9, 16);

        $this->assertSame('late', AttendanceCalculator::statusForClockIn($this->shift(), $clockIn));
    }

    public function test_working_at_least_half_the_shift_keeps_the_provisional_status(): void
    {
        $this->assertSame('present', AttendanceCalculator::finalStatus('present', 270, $this->shift()));
        $this->assertSame('late', AttendanceCalculator::finalStatus('late', 270, $this->shift()));
    }

    public function test_working_less_than_half_the_shift_is_half_day(): void
    {
        $this->assertSame('half_day', AttendanceCalculator::finalStatus('present', 269, $this->shift()));
    }

    public function test_shift_duration_minutes_is_computed_correctly(): void
    {
        $this->assertSame(540, AttendanceCalculator::shiftDurationMinutes($this->shift()));
    }
}
