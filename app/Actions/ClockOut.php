<?php

namespace App\Actions;

use App\Models\ActivityLog;
use App\Models\AttendanceRecord;
use App\Models\User;
use App\Services\AttendanceCalculator;
use Illuminate\Validation\ValidationException;

class ClockOut
{
    public function execute(User $user): AttendanceRecord
    {
        $today = now()->toDateString();

        $record = AttendanceRecord::where('user_id', $user->id)->where('date', $today)->first();

        if (! $record || ! $record->clock_in) {
            throw ValidationException::withMessages(['clock_out' => 'You have not clocked in today.']);
        }

        if ($record->clock_out) {
            throw ValidationException::withMessages(['clock_out' => 'You have already clocked out today.']);
        }

        $now = now();
        $workedMinutes = (int) $record->clock_in->diffInMinutes($now);
        $shift = $user->department?->effectiveShift() ?? AttendanceCalculator::defaultShift();

        $record->update([
            'clock_out' => $now,
            'worked_minutes' => $workedMinutes,
            'status' => AttendanceCalculator::finalStatus($record->status, $workedMinutes, $shift),
        ]);

        ActivityLog::record(
            'attendance.clocked_out',
            $user,
            "{$user->name} clocked out.",
            ['attendance_record_id' => $record->id, 'worked_minutes' => $workedMinutes]
        );

        return $record;
    }
}
