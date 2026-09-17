<?php

namespace App\Actions;

use App\Models\ActivityLog;
use App\Models\AttendanceRegularization;
use App\Models\User;

class RequestAttendanceRegularization
{
    /**
     * @param  array{date: string, requested_clock_in: string, requested_clock_out: string, reason: string}  $data
     */
    public function execute(User $user, array $data): AttendanceRegularization
    {
        $regularization = AttendanceRegularization::create([
            'user_id' => $user->id,
            'date' => $data['date'],
            'requested_clock_in' => $data['date'].' '.$data['requested_clock_in'],
            'requested_clock_out' => $data['date'].' '.$data['requested_clock_out'],
            'reason' => $data['reason'],
            'status' => 'pending',
        ]);

        ActivityLog::record(
            'attendance_regularization.created',
            $user,
            "{$user->name} requested an attendance correction for {$data['date']}.",
            ['attendance_regularization_id' => $regularization->id]
        );

        return $regularization;
    }
}
