<?php

namespace App\Actions;

use App\Models\ActivityLog;
use App\Models\LeaveRequest;

class CancelLeaveRequest
{
    public function execute(LeaveRequest $leaveRequest): LeaveRequest
    {
        $leaveRequest->update(['status' => 'cancelled']);

        ActivityLog::record(
            'leave.cancelled',
            $leaveRequest->user,
            "{$leaveRequest->user->name} cancelled their leave request.",
            ['leave_request_id' => $leaveRequest->id]
        );

        return $leaveRequest;
    }
}
