<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\DepartmentShift;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AttendanceClockTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(?Department $department = null): User
    {
        $role = Role::create(['name' => 'user-'.uniqid(), 'type' => 'hierarchy', 'level' => 10]);

        return User::factory()->create(['role_id' => $role->id, 'department_id' => $department?->id]);
    }

    private function departmentWithShift(string $start = '09:00:00', string $end = '18:00:00', int $grace = 15): Department
    {
        $department = Department::create(['name' => 'Engineering-'.uniqid()]);

        DepartmentShift::create([
            'department_id' => $department->id,
            'start_time' => $start,
            'end_time' => $end,
            'grace_minutes' => $grace,
        ]);

        return $department;
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_guest_cannot_access_attendance_page(): void
    {
        $response = $this->get('/attendance');

        $response->assertRedirect('/login');
    }

    public function test_user_can_view_their_attendance_page(): void
    {
        $user = $this->makeUser();

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertStatus(200);
    }

    public function test_user_can_clock_in_on_time(): void
    {
        $department = $this->departmentWithShift();
        $user = $this->makeUser($department);

        Carbon::setTestNow(Carbon::today()->setTime(9, 5));

        $response = $this->actingAs($user)->post('/attendance/clock-in');

        $response->assertRedirect(route('attendance.index'));
        $this->assertDatabaseHas('attendance_records', [
            'user_id' => $user->id,
            'status' => 'present',
        ]);
    }

    public function test_user_who_clocks_in_late_is_marked_late(): void
    {
        $department = $this->departmentWithShift();
        $user = $this->makeUser($department);

        Carbon::setTestNow(Carbon::today()->setTime(9, 45));

        $this->actingAs($user)->post('/attendance/clock-in');

        $this->assertDatabaseHas('attendance_records', [
            'user_id' => $user->id,
            'status' => 'late',
        ]);
    }

    public function test_user_cannot_clock_in_twice_in_one_day(): void
    {
        $department = $this->departmentWithShift();
        $user = $this->makeUser($department);

        $this->actingAs($user)->post('/attendance/clock-in');
        $response = $this->actingAs($user)->post('/attendance/clock-in');

        $response->assertSessionHasErrors('clock_in');
        $this->assertDatabaseCount('attendance_records', 1);
    }

    public function test_user_on_approved_leave_cannot_clock_in(): void
    {
        $department = $this->departmentWithShift();
        $user = $this->makeUser($department);
        $leaveType = LeaveType::create(['name' => 'Annual-'.uniqid(), 'default_days_per_year' => 18]);

        LeaveRequest::create([
            'user_id' => $user->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->toDateString(),
            'total_days' => 1,
            'status' => 'approved',
        ]);

        $response = $this->actingAs($user)->post('/attendance/clock-in');

        $response->assertSessionHasErrors('clock_in');
        $this->assertDatabaseCount('attendance_records', 0);
    }

    public function test_user_can_clock_out_and_worked_minutes_are_recorded(): void
    {
        $department = $this->departmentWithShift();
        $user = $this->makeUser($department);

        Carbon::setTestNow(Carbon::today()->setTime(9, 0));
        $this->actingAs($user)->post('/attendance/clock-in');

        Carbon::setTestNow(Carbon::today()->setTime(17, 0));
        $response = $this->actingAs($user)->post('/attendance/clock-out');

        $response->assertRedirect(route('attendance.index'));
        $this->assertDatabaseHas('attendance_records', [
            'user_id' => $user->id,
            'worked_minutes' => 480,
            'status' => 'present',
        ]);
    }

    public function test_user_who_works_less_than_half_the_shift_is_marked_half_day(): void
    {
        $department = $this->departmentWithShift();
        $user = $this->makeUser($department);

        Carbon::setTestNow(Carbon::today()->setTime(9, 0));
        $this->actingAs($user)->post('/attendance/clock-in');

        Carbon::setTestNow(Carbon::today()->setTime(11, 0));
        $this->actingAs($user)->post('/attendance/clock-out');

        $this->assertDatabaseHas('attendance_records', [
            'user_id' => $user->id,
            'status' => 'half_day',
        ]);
    }

    public function test_user_cannot_clock_out_without_clocking_in(): void
    {
        $user = $this->makeUser();

        $response = $this->actingAs($user)->post('/attendance/clock-out');

        $response->assertSessionHasErrors('clock_out');
    }
}
