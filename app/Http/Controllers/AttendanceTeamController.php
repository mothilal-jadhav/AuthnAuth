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

        // Joined (rather than sorted in PHP after ->get()) so the ordering
        // can happen at the DB level and the result set can be paginated —
        // at a few thousand employees, a single day's roster is itself
        // thousands of rows.
        $records = AttendanceRecord::query()
            ->join('users', 'users.id', '=', 'attendance_records.user_id')
            ->with(['user.department'])
            ->where('attendance_records.date', $dateString)
            ->when(
                $request->filled('department_id'),
                fn ($query) => $query->where('users.department_id', $request->input('department_id'))
            )
            ->orderBy('users.name')
            ->select('attendance_records.*')
            ->paginate(25)
            ->withQueryString();

        return view('attendance.team.index', compact('records', 'departments', 'dateString'));
    }
}
