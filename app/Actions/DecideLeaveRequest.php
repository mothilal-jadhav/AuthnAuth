<?php

namespace App\Actions;

use App\Models\ActivityLog;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Notifications\LeaveDecisionNotification;

class DecideLeaveRequest
{
    /**
     * @param  'approved'|'rejected'  $decision
     */
    public function execute(LeaveRequest $leaveRequest, User $approver, string $decision, ?string $note = null): LeaveRequest
    {
        // Guards against a double form-submit re-applying the balance
        // increment on an already-decided request.
        if ($leaveRequest->status !== 'pending') {
            return $leaveRequest;
        }

        $leaveRequest->update([
            'status' => $decision,
            'approver_id' => $approver->id,
            'decision_note' => $note,
            'decided_at' => now(),
        ]);

        if ($decision === 'approved' && ! $leaveRequest->leaveType->is_unlimited) {
            $balance = LeaveBalance::firstOrCreate(
                [
                    'user_id' => $leaveRequest->user_id,
                    'leave_type_id' => $leaveRequest->leave_type_id,
                    'year' => $leaveRequest->start_date->year,
                ],
                ['allocated_days' => $leaveRequest->leaveType->default_days_per_year]
            );

            $balance->increment('used_days', $leaveRequest->total_days);
        }

        ActivityLog::record(
            "leave.{$decision}",
            $leaveRequest->user,
            "{$approver->name} {$decision} {$leaveRequest->user->name}'s leave request.",
            ['leave_request_id' => $leaveRequest->id]
        );

        $leaveRequest->user->notify(new LeaveDecisionNotification($leaveRequest->fresh(['leaveType'])));

        return $leaveRequest;
    }
}
