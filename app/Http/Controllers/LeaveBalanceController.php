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
        $now = now();

        // Lazily backfill a balance row for every user x active leave type
        // for the current year, so admins always see a complete grid rather
        // than having to guess which rows are simply missing vs. zero.
        //
        // This used to be a firstOrCreate() per user x leave type -- one to
        // two queries each, so O(users x types) round trips every single
        // page load (20,000+ queries at a few thousand users, measured).
        // Instead: one query to see which pairs already exist, then a
        // single chunked bulk insert for whatever's missing (only ever
        // non-empty on a user's or leave type's first visit).
        $activeTypes = LeaveType::where('is_active', true)->get();
        $userIds = User::pluck('id');

        $existingPairs = LeaveBalance::where('year', $year)
            ->get(['user_id', 'leave_type_id'])
            ->map(fn ($balance) => "{$balance->user_id}:{$balance->leave_type_id}")
            ->flip();

        $missing = [];

        foreach ($userIds as $userId) {
            foreach ($activeTypes as $type) {
                if (! isset($existingPairs["{$userId}:{$type->id}"])) {
                    $missing[] = [
                        'user_id' => $userId,
                        'leave_type_id' => $type->id,
                        'year' => $year,
                        'allocated_days' => $type->default_days_per_year,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }
        }

        foreach (array_chunk($missing, 500) as $chunk) {
            LeaveBalance::insert($chunk);
        }

        // Joined (rather than sorted in PHP after ->get()) so the ordering
        // can happen at the DB level and the result set can be paginated —
        // at a few thousand employees this listing is users x leave types
        // rows. whereNull('users.deleted_at') replaces the old
        // whereHas('user') existence check, since a plain join doesn't
        // apply the User model's SoftDeletes global scope on its own.
        $balances = LeaveBalance::query()
            ->join('users', 'users.id', '=', 'leave_balances.user_id')
            ->with(['user', 'leaveType'])
            ->where('leave_balances.year', $year)
            ->whereNull('users.deleted_at')
            ->orderBy('users.name')
            ->orderBy('leave_balances.leave_type_id')
            ->select('leave_balances.*')
            ->paginate(25);

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
