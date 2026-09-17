<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDepartmentRequest;
use App\Http\Requests\UpdateDepartmentRequest;
use App\Models\ActivityLog;
use App\Models\Department;
use App\Models\User;

class DepartmentController extends Controller
{
    public function index()
    {
        $departments = Department::with('head')
            ->withCount('users')
            ->orderBy('name')
            ->paginate(25);

        return view('departments.index', compact('departments'));
    }

    public function show(Department $department)
    {
        $department->load('head');
        $users = $department->users()->orderBy('name')->paginate(25);

        return view('departments.show', compact('department', 'users'));
    }

    public function create()
    {
        $users = User::orderBy('name')->get();

        return view('departments.create', compact('users'));
    }

    public function store(StoreDepartmentRequest $request)
    {
        $department = Department::create($request->validated());

        ActivityLog::record(
            'department.created',
            null,
            auth()->user()->name." created the {$department->name} department.",
            ['department_id' => $department->id]
        );

        return redirect()
            ->route('departments.index')
            ->with('success', 'Department created successfully.');
    }

    public function edit(Department $department)
    {
        $users = User::orderBy('name')->get();

        return view('departments.edit', compact('department', 'users'));
    }

    public function update(UpdateDepartmentRequest $request, Department $department)
    {
        $department->update($request->validated());

        ActivityLog::record(
            'department.updated',
            null,
            auth()->user()->name." updated the {$department->name} department.",
            ['department_id' => $department->id]
        );

        return redirect()
            ->route('departments.index')
            ->with('success', 'Department updated successfully.');
    }

    public function destroy(Department $department)
    {
        $name = $department->name;
        $id = $department->id;

        $department->delete();

        ActivityLog::record(
            'department.deleted',
            null,
            auth()->user()->name." deleted the {$name} department.",
            ['department_id' => $id]
        );

        return redirect()
            ->route('departments.index')
            ->with('success', 'Department deleted successfully.');
    }
}
