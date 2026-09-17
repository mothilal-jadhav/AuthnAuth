<?php

namespace App\Actions;

use App\Models\ActivityLog;
use App\Models\AttendanceRecord;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Services\AttendanceCalculator;
use Illuminate\Validation\ValidationException;

class ClockIn
{
    public function execute(User $user): AttendanceRecord
    {
        $today = now()->toDateString();

        $alreadyClockedIn = AttendanceRecord::where('user_id', $user->id)
            ->where('date', $today)
            ->whereNotNull('clock_in')
            ->exists();

        if ($alreadyClockedIn) {
            throw ValidationException::withMessages(['clock_in' => 'You have already clocked in today.']);
        }

        $onApprovedLeave = LeaveRequest::where('user_id', $user->id)
            ->where('status', 'approved')
            ->where('start_date', '<=', $today)
            ->where('end_date', '>=', $today)
            ->exists();

        if ($onApprovedLeave) {
            throw ValidationException::withMessages(['clock_in' => 'You are on approved leave today.']);
        }

        $shift = $user->department?->effectiveShift() ?? AttendanceCalculator::defaultShift();
        $now = now();

        $record = AttendanceRecord::updateOrCreate(
            ['user_id' => $user->id, 'date' => $today],
            ['clock_in' => $now, 'status' => AttendanceCalculator::statusForClockIn($shift, $now)]
        );

        ActivityLog::record(
            'attendance.clocked_in',
            $user,
            "{$user->name} clocked in.",
            ['attendance_record_id' => $record->id]
        );

        return $record;
    }
}
