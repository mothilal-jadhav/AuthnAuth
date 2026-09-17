<?php

namespace App\Services;

use Illuminate\Support\Carbon;

class AttendanceCalculator
{
    /**
     * The shift to use when a department has no `DepartmentShift` row yet.
     *
     * @return array{start_time: string, end_time: string, grace_minutes: int}
     */
    public static function defaultShift(): array
    {
        return [
            'start_time' => (string) config('attendance.default_start'),
            'end_time' => (string) config('attendance.default_end'),
            'grace_minutes' => (int) config('attendance.default_grace_minutes'),
        ];
    }

    /**
     * @param  array{start_time: string, end_time: string, grace_minutes: int}  $shift
     */
    public static function statusForClockIn(array $shift, Carbon $clockIn): string
    {
        $shiftStart = Carbon::parse($clockIn->format('Y-m-d').' '.$shift['start_time'])
            ->addMinutes($shift['grace_minutes']);

        return $clockIn->greaterThan($shiftStart) ? 'late' : 'present';
    }

    /**
     * Half-day if worked less than half the shift's total minutes,
     * otherwise keeps the provisional present/late call from clock-in.
     *
     * @param  array{start_time: string, end_time: string, grace_minutes: int}  $shift
     */
    public static function finalStatus(string $provisionalStatus, int $workedMinutes, array $shift): string
    {
        $shiftMinutes = self::shiftDurationMinutes($shift);

        if ($shiftMinutes > 0 && $workedMinutes < ($shiftMinutes / 2)) {
            return 'half_day';
        }

        return $provisionalStatus;
    }

    /**
     * @param  array{start_time: string, end_time: string, grace_minutes: int}  $shift
     */
    public static function shiftDurationMinutes(array $shift): int
    {
        $start = Carbon::parse($shift['start_time']);
        $end = Carbon::parse($shift['end_time']);

        return (int) $start->diffInMinutes($end);
    }
}
