<?php

namespace Tests\Feature\Console;

use App\Models\AttendanceRecord;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarkAbsenteesCommandTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(): User
    {
        $role = Role::create(['name' => 'user-'.uniqid(), 'type' => 'hierarchy', 'level' => 10]);

        return User::factory()->create(['role_id' => $role->id]);
    }

    public function test_marks_a_user_with_no_record_as_absent(): void
    {
        $user = $this->makeUser();
        $date = now()->subDay()->toDateString();

        $this->artisan('attendance:mark-absentees', ['--date' => $date])->assertExitCode(0);

        $this->assertDatabaseHas('attendance_records', [
            'user_id' => $user->id,
            'date' => $date,
            'status' => 'absent',
        ]);
    }

    public function test_marks_a_user_with_approved_leave_as_on_leave(): void
    {
        $user = $this->makeUser();
        $leaveType = LeaveType::create(['name' => 'Annual-'.uniqid(), 'default_days_per_year' => 18]);
        $date = now()->subDay()->toDateString();

        LeaveRequest::create([
            'user_id' => $user->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => $date,
            'end_date' => $date,
            'total_days' => 1,
            'status' => 'approved',
        ]);

        $this->artisan('attendance:mark-absentees', ['--date' => $date]);

        $this->assertDatabaseHas('attendance_records', [
            'user_id' => $user->id,
            'date' => $date,
            'status' => 'on_leave',
        ]);
    }

    public function test_skips_a_user_who_already_has_a_record(): void
    {
        $user = $this->makeUser();
        $date = now()->subDay()->toDateString();

        AttendanceRecord::create([
            'user_id' => $user->id,
            'date' => $date,
            'clock_in' => $date.' 09:00:00',
            'status' => 'present',
        ]);

        $this->artisan('attendance:mark-absentees', ['--date' => $date]);

        $this->assertDatabaseCount('attendance_records', 1);
        $this->assertDatabaseHas('attendance_records', ['user_id' => $user->id, 'date' => $date, 'status' => 'present']);
    }

    public function test_command_is_idempotent_on_rerun(): void
    {
        $user = $this->makeUser();
        $date = now()->subDay()->toDateString();

        $this->artisan('attendance:mark-absentees', ['--date' => $date]);
        $this->artisan('attendance:mark-absentees', ['--date' => $date]);

        $this->assertDatabaseCount('attendance_records', 1);
    }
}
