<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateLeaveBalanceRequest;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Models\User;

class LeaveBalanceController extends Controller
{
    public function index()
    {
        $year = now()->year;

        // Lazily backfill a balance row for every user x active leave type
        // for the current year, so admins always see a complete grid rather
        // than having to guess which rows are simply missing vs. zero.
        $activeTypes = LeaveType::where('is_active', true)->get();

        foreach (User::all() as $user) {
            foreach ($activeTypes as $type) {
                LeaveBalance::firstOrCreate(
                    ['user_id' => $user->id, 'leave_type_id' => $type->id, 'year' => $year],
                    ['allocated_days' => $type->default_days_per_year]
                );
            }
        }

        $balances = LeaveBalance::with(['user', 'leaveType'])
            ->where('year', $year)
            ->whereHas('user')
            ->get()
            ->sortBy(fn ($balance) => $balance->user->name)
            ->values();

        return view('leave.balances.index', compact('balances', 'year'));
    }

    public function edit(LeaveBalance $leaveBalance)
    {
        $leaveBalance->load(['user', 'leaveType']);

        return view('leave.balances.edit', compact('leaveBalance'));
    }

    public function update(UpdateLeaveBalanceRequest $request, LeaveBalance $leaveBalance)
    {
        $leaveBalance->update($request->validated());

        return redirect()->route('leave.balances.index')->with('success', 'Leave balance updated successfully.');
    }
}
