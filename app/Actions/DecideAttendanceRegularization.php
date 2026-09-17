<?php

namespace App\Actions;

use App\Models\ActivityLog;
use App\Models\AttendanceRecord;
use App\Models\AttendanceRegularization;
use App\Models\User;
use App\Services\AttendanceCalculator;

class DecideAttendanceRegularization
{
    /**
     * @param  'approved'|'rejected'  $decision
     */
    public function execute(AttendanceRegularization $regularization, User $approver, string $decision, ?string $note = null): AttendanceRegularization
    {
        // Guards against a double form-submit re-applying the attendance
        // record write on an already-decided request.
        if ($regularization->status !== 'pending') {
            return $regularization;
        }

        $regularization->update([
            'status' => $decision,
            'approver_id' => $approver->id,
            'decision_note' => $note,
            'decided_at' => now(),
        ]);

        if ($decision === 'approved') {
            $workedMinutes = (int) $regularization->requested_clock_in->diffInMinutes($regularization->requested_clock_out);
            $shift = $regularization->user->department?->effectiveShift() ?? AttendanceCalculator::defaultShift();
            $status = AttendanceCalculator::finalStatus(
                AttendanceCalculator::statusForClockIn($shift, $regularization->requested_clock_in),
                $workedMinutes,
                $shift
            );

            AttendanceRecord::updateOrCreate(
                ['user_id' => $regularization->user_id, 'date' => $regularization->date->toDateString()],
                [
                    'clock_in' => $regularization->requested_clock_in,
                    'clock_out' => $regularization->requested_clock_out,
                    'worked_minutes' => $workedMinutes,
                    'status' => $status,
                    'notes' => 'Corrected via regularization #'.$regularization->id,
                ]
            );
        }

        ActivityLog::record(
            "attendance_regularization.{$decision}",
            $regularization->user,
            "{$approver->name} {$decision} {$regularization->user->name}'s attendance correction request.",
            ['attendance_regularization_id' => $regularization->id]
        );

        return $regularization;
    }
}
