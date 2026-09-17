<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateDepartmentShiftRequest;
use App\Models\Department;

class DepartmentShiftController extends Controller
{
    public function index()
    {
        $departments = Department::with('shift')->orderBy('name')->get();

        return view('attendance.shifts.index', compact('departments'));
    }

    public function edit(Department $department)
    {
        $shift = $department->effectiveShift();

        return view('attendance.shifts.edit', compact('department', 'shift'));
    }

    public function update(UpdateDepartmentShiftRequest $request, Department $department)
    {
        $department->shift()->updateOrCreate([], $request->validated());

        return redirect()->route('attendance.shifts.index')->with('success', 'Shift updated successfully.');
    }
}
