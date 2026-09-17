<?php

namespace App\Http\Controllers;

use App\Actions\RequestAttendanceRegularization;
use App\Http\Requests\StoreAttendanceRegularizationRequest;

class AttendanceRegularizationController extends Controller
{
    public function create()
    {
        return view('attendance.regularize.create');
    }

    public function store(StoreAttendanceRegularizationRequest $request, RequestAttendanceRegularization $requestAttendanceRegularization)
    {
        $requestAttendanceRegularization->execute($request->user(), $request->validated());

        return redirect()->route('attendance.index')->with('success', 'Correction request submitted successfully.');
    }
}
