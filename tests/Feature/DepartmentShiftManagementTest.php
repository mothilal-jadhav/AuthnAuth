<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepartmentShiftManagementTest extends TestCase
{
    use RefreshDatabase;

    private function actorWithPermissions(array $permissions): User
    {
        $role = Role::create(['name' => 'manager-'.uniqid(), 'type' => 'hierarchy', 'level' => 50]);

        foreach ($permissions as $name) {
            $permission = Permission::firstOrCreate(['name' => $name]);
            $role->permissions()->attach($permission);
        }

        return User::factory()->create(['role_id' => $role->id]);
    }

    public function test_guest_cannot_access_shift_settings(): void
    {
        $response = $this->get('/attendance/shifts');

        $response->assertRedirect('/login');
    }

    public function test_user_with_attendance_manage_permission_can_view_shift_settings(): void
    {
        $actor = $this->actorWithPermissions(['attendance.manage']);

        $response = $this->actingAs($actor)->get('/attendance/shifts');

        $response->assertStatus(200);
    }

    public function test_user_without_attendance_manage_permission_cannot_view_shift_settings(): void
    {
        $actor = $this->actorWithPermissions([]);

        $response = $this->actingAs($actor)->get('/attendance/shifts');

        $response->assertStatus(403);
    }

    public function test_user_can_configure_a_departments_shift(): void
    {
        $actor = $this->actorWithPermissions(['attendance.manage']);
        $department = Department::create(['name' => 'Engineering']);

        $response = $this->actingAs($actor)->put("/attendance/shifts/{$department->id}", [
            'start_time' => '08:30',
            'end_time' => '17:30',
            'grace_minutes' => 10,
        ]);

        $response->assertRedirect(route('attendance.shifts.index'));
        $this->assertDatabaseHas('department_shifts', [
            'department_id' => $department->id,
            'grace_minutes' => 10,
        ]);
    }

    public function test_editing_a_departments_shift_updates_the_same_row_not_a_new_one(): void
    {
        $actor = $this->actorWithPermissions(['attendance.manage']);
        $department = Department::create(['name' => 'Engineering']);

        $this->actingAs($actor)->put("/attendance/shifts/{$department->id}", [
            'start_time' => '08:30', 'end_time' => '17:30', 'grace_minutes' => 10,
        ]);
        $this->actingAs($actor)->put("/attendance/shifts/{$department->id}", [
            'start_time' => '09:00', 'end_time' => '18:00', 'grace_minutes' => 15,
        ]);

        $this->assertDatabaseCount('department_shifts', 1);
        $this->assertDatabaseHas('department_shifts', ['department_id' => $department->id, 'grace_minutes' => 15]);
    }
}
