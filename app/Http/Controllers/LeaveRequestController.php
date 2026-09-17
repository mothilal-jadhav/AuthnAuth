<?php

namespace App\Http\Controllers;

use App\Actions\CancelLeaveRequest;
use App\Actions\SubmitLeaveRequest;
use App\Http\Requests\StoreLeaveRequestRequest;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;

class LeaveRequestController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $year = now()->year;

        $leaveTypes = LeaveType::where('is_active', true)->orderBy('name')->get();

        $balances = LeaveBalance::where('user_id', $user->id)
            ->where('year', $year)
            ->get()
            ->keyBy('leave_type_id');

        $requests = LeaveRequest::where('user_id', $user->id)
            ->with('leaveType')
            ->latest('start_date')
            ->paginate(15);

        return view('leave.index', compact('leaveTypes', 'balances', 'requests', 'year'));
    }

    public function create()
    {
        $leaveTypes = LeaveType::where('is_active', true)->orderBy('name')->get();

        return view('leave.create', compact('leaveTypes'));
    }

    public function store(StoreLeaveRequestRequest $request, SubmitLeaveRequest $submitLeaveRequest)
    {
        $submitLeaveRequest->execute($request->user(), $request->validated());

        return redirect()->route('leave.index')->with('success', 'Leave request submitted successfully.');
    }

    public function show(LeaveRequest $leaveRequest)
    {
        $this->authorize('view', $leaveRequest);

        $leaveRequest->load(['leaveType', 'approver']);

        return view('leave.show', compact('leaveRequest'));
    }

    public function cancel(LeaveRequest $leaveRequest, CancelLeaveRequest $cancelLeaveRequest)
    {
        $this->authorize('cancel', $leaveRequest);

        $cancelLeaveRequest->execute($leaveRequest);

        return redirect()->route('leave.index')->with('success', 'Leave request cancelled.');
    }
}
