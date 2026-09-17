<?php

namespace App\Console\Commands;

use App\Models\AttendanceRecord;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

#[Signature('attendance:mark-absentees {--date=}')]
#[Description('Backfill attendance records for users with no clock-in on a finished day, marking them absent or on_leave')]
class MarkAbsentees extends Command
{
    public function handle(): int
    {
        $date = $this->option('date') ? Carbon::parse($this->option('date')) : now()->subDay();
        $dateString = $date->toDateString();

        $usersWithRecords = AttendanceRecord::where('date', $dateString)->pluck('user_id');

        $approvedLeaveUserIds = LeaveRequest::where('status', 'approved')
            ->where('start_date', '<=', $dateString)
            ->where('end_date', '>=', $dateString)
            ->pluck('user_id');

        $missingUsers = User::whereNotIn('id', $usersWithRecords)->get();

        foreach ($missingUsers as $user) {
            AttendanceRecord::create([
                'user_id' => $user->id,
                'date' => $dateString,
                'status' => $approvedLeaveUserIds->contains($user->id) ? 'on_leave' : 'absent',
            ]);
        }

        $this->info("Processed {$missingUsers->count()} user(s) for {$dateString}.");

        return self::SUCCESS;
    }
}
