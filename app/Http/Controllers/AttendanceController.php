<?php

namespace App\Http\Controllers;

use App\Actions\ClockIn;
use App\Actions\ClockOut;
use App\Models\AttendanceRecord;

class AttendanceController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $today = AttendanceRecord::where('user_id', $user->id)->where('date', now()->toDateString())->first();

        $month = now();

        $records = AttendanceRecord::where('user_id', $user->id)
            ->forMonth((int) $month->year, (int) $month->month)
            ->orderByDesc('date')
            ->paginate(31);

        $counts = AttendanceRecord::where('user_id', $user->id)
            ->forMonth((int) $month->year, (int) $month->month)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('attendance.index', compact('today', 'records', 'counts'));
    }

    public function clockIn(ClockIn $clockIn)
    {
        $clockIn->execute(auth()->user());

        return redirect()->route('attendance.index')->with('success', 'Clocked in successfully.');
    }

    public function clockOut(ClockOut $clockOut)
    {
        $clockOut->execute(auth()->user());

        return redirect()->route('attendance.index')->with('success', 'Clocked out successfully.');
    }
}
