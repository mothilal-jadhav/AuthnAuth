<?php

namespace App\Http\Controllers;

use App\Actions\DecideAttendanceRegularization;
use App\Http\Requests\RejectAttendanceRegularizationRequest;
use App\Models\AttendanceRegularization;

class AttendanceRegularizationApprovalController extends Controller
{
    public function index()
    {
        $pending = AttendanceRegularization::pending()
            ->with('user')
            ->oldest('date')
            ->paginate(15);

        return view('attendance.regularizations.index', compact('pending'));
    }

    public function approve(AttendanceRegularization $attendanceRegularization, DecideAttendanceRegularization $decideAttendanceRegularization)
    {
        $this->authorize('decide', $attendanceRegularization);

        $decideAttendanceRegularization->execute($attendanceRegularization, auth()->user(), 'approved');

        return redirect()->route('attendance.regularizations.index')->with('success', 'Correction request approved.');
    }

    public function reject(RejectAttendanceRegularizationRequest $request, AttendanceRegularization $attendanceRegularization, DecideAttendanceRegularization $decideAttendanceRegularization)
    {
        $decideAttendanceRegularization->execute($attendanceRegularization, auth()->user(), 'rejected', $request->validated()['decision_note']);

        return redirect()->route('attendance.regularizations.index')->with('success', 'Correction request rejected.');
    }
}
