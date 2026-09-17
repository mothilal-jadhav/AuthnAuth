<?php

namespace App\Http\Controllers;

use App\Actions\DecideLeaveRequest;
use App\Http\Requests\RejectLeaveRequestRequest;
use App\Models\LeaveRequest;

class LeaveApprovalController extends Controller
{
    public function index()
    {
        $pending = LeaveRequest::pending()
            ->with(['user', 'leaveType'])
            ->oldest('start_date')
            ->paginate(15);

        return view('leave.approvals.index', compact('pending'));
    }

    public function approve(LeaveRequest $leaveRequest, DecideLeaveRequest $decideLeaveRequest)
    {
        $this->authorize('decide', $leaveRequest);

        $decideLeaveRequest->execute($leaveRequest, auth()->user(), 'approved');

        return redirect()->route('leave.approvals.index')->with('success', 'Leave request approved.');
    }

    public function reject(RejectLeaveRequestRequest $request, LeaveRequest $leaveRequest, DecideLeaveRequest $decideLeaveRequest)
    {
        $decideLeaveRequest->execute($leaveRequest, auth()->user(), 'rejected', $request->validated()['decision_note']);

        return redirect()->route('leave.approvals.index')->with('success', 'Leave request rejected.');
    }
}
