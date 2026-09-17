<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceTeamViewTest extends TestCase
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

    public function test_guest_cannot_access_team_attendance_page(): void
    {
        $response = $this->get('/attendance/team');

        $response->assertRedirect('/login');
    }

    public function test_user_with_attendance_view_permission_can_access_team_attendance_page(): void
    {
        $actor = $this->actorWithPermissions(['attendance.view']);

        $response = $this->actingAs($actor)->get('/attendance/team');

        $response->assertStatus(200);
    }

    public function test_user_without_attendance_view_permission_cannot_access_team_attendance_page(): void
    {
        $actor = $this->actorWithPermissions([]);

        $response = $this->actingAs($actor)->get('/attendance/team');

        $response->assertStatus(403);
    }
}
