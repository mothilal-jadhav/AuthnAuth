<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AttendanceTeamController extends Controller
{
    public function index(Request $request)
    {
        $date = $request->filled('date') ? Carbon::parse($request->input('date')) : now();
        $dateString = $date->toDateString();

        $departments = Department::orderBy('name')->get();

        $records = AttendanceRecord::with(['user.department'])
            ->where('date', $dateString)
            ->when($request->filled('department_id'), fn ($query) => $query->whereHas(
                'user',
                fn ($q) => $q->where('department_id', $request->input('department_id'))
            ))
            ->get()
            ->sortBy(fn ($record) => $record->user->name)
            ->values();

        return view('attendance.team.index', compact('records', 'departments', 'dateString'));
    }
}
