<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLeaveTypeRequest;
use App\Http\Requests\UpdateLeaveTypeRequest;
use App\Models\ActivityLog;
use App\Models\LeaveType;

class LeaveTypeController extends Controller
{
    public function index()
    {
        $leaveTypes = LeaveType::orderBy('name')->paginate(25);

        return view('leave.types.index', compact('leaveTypes'));
    }

    public function create()
    {
        return view('leave.types.create');
    }

    public function store(StoreLeaveTypeRequest $request)
    {
        $leaveType = LeaveType::create($request->validated());

        ActivityLog::record(
            'leave_type.created',
            null,
            auth()->user()->name." created the {$leaveType->name} leave type.",
            ['leave_type_id' => $leaveType->id]
        );

        return redirect()->route('leave.types.index')->with('success', 'Leave type created successfully.');
    }

    public function edit(LeaveType $leaveType)
    {
        return view('leave.types.edit', compact('leaveType'));
    }

    public function update(UpdateLeaveTypeRequest $request, LeaveType $leaveType)
    {
        $leaveType->update($request->validated());

        ActivityLog::record(
            'leave_type.updated',
            null,
            auth()->user()->name." updated the {$leaveType->name} leave type.",
            ['leave_type_id' => $leaveType->id]
        );

        return redirect()->route('leave.types.index')->with('success', 'Leave type updated successfully.');
    }

    /**
     * Leave types are referenced by historical requests/balances, so
     * "delete" here archives (is_active = false) rather than removing the
     * row — unlike Department, which has no such downstream history to
     * preserve.
     */
    public function destroy(LeaveType $leaveType)
    {
        $leaveType->update(['is_active' => false]);

        ActivityLog::record(
            'leave_type.archived',
            null,
            auth()->user()->name." archived the {$leaveType->name} leave type.",
            ['leave_type_id' => $leaveType->id]
        );

        return redirect()->route('leave.types.index')->with('success', 'Leave type archived successfully.');
    }
}
