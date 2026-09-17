<?php

namespace App\Actions;

use App\Models\ActivityLog;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Services\LeaveCalculator;

class SubmitLeaveRequest
{
    /**
     * @param  array{leave_type_id: int, start_date: string, end_date: string, is_half_day?: bool, reason?: string|null}  $data
     */
    public function execute(User $user, array $data): LeaveRequest
    {
        $isHalfDay = $data['is_half_day'] ?? false;

        $totalDays = LeaveCalculator::workingDaysBetween($data['start_date'], $data['end_date'], $isHalfDay);

        $leaveRequest = LeaveRequest::create([
            'user_id' => $user->id,
            'leave_type_id' => $data['leave_type_id'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'is_half_day' => $isHalfDay,
            'total_days' => $totalDays,
            'reason' => $data['reason'] ?? null,
            'status' => 'pending',
        ]);

        ActivityLog::record(
            'leave.created',
            $user,
            "{$user->name} applied for {$totalDays} day(s) of leave.",
            ['leave_request_id' => $leaveRequest->id]
        );

        return $leaveRequest;
    }
}
