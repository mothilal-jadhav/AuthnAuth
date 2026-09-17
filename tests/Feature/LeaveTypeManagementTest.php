<?php

namespace Tests\Feature;

use App\Models\LeaveType;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaveTypeManagementTest extends TestCase
{
    use RefreshDatabase;

    private function actorWithPermissions(array $permissions): User
    {
        $role = Role::create(['name' => 'manager', 'type' => 'hierarchy', 'level' => 50]);

        foreach ($permissions as $name) {
            $permission = Permission::firstOrCreate(['name' => $name]);
            $role->permissions()->attach($permission);
        }

        return User::factory()->create(['role_id' => $role->id]);
    }

    public function test_guest_cannot_access_leave_types_page(): void
    {
        $response = $this->get('/leave/types');

        $response->assertRedirect('/login');
    }

    public function test_user_with_leave_manage_permission_can_view_leave_types_page(): void
    {
        $actor = $this->actorWithPermissions(['leave.manage']);

        $response = $this->actingAs($actor)->get('/leave/types');

        $response->assertStatus(200);
    }

    public function test_user_without_leave_manage_permission_cannot_view_leave_types_page(): void
    {
        $actor = $this->actorWithPermissions([]);

        $response = $this->actingAs($actor)->get('/leave/types');

        $response->assertStatus(403);
    }

    public function test_user_with_leave_manage_permission_can_create_leave_type(): void
    {
        $actor = $this->actorWithPermissions(['leave.manage']);

        $response = $this->actingAs($actor)->post('/leave/types', [
            'name' => 'Sick',
            'default_days_per_year' => 12,
            'paid' => '1',
        ]);

        $response->assertRedirect(route('leave.types.index'));
        $this->assertDatabaseHas('leave_types', ['name' => 'Sick', 'default_days_per_year' => 12]);
    }

    public function test_user_without_leave_manage_permission_cannot_create_leave_type(): void
    {
        $actor = $this->actorWithPermissions([]);

        $response = $this->actingAs($actor)->post('/leave/types', [
            'name' => 'Sick',
            'default_days_per_year' => 12,
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseMissing('leave_types', ['name' => 'Sick']);
    }

    public function test_user_with_leave_manage_permission_can_update_leave_type(): void
    {
        $actor = $this->actorWithPermissions(['leave.manage']);
        $leaveType = LeaveType::create(['name' => 'Casual', 'default_days_per_year' => 6]);

        $response = $this->actingAs($actor)->put("/leave/types/{$leaveType->id}", [
            'name' => 'Casual',
            'default_days_per_year' => 8,
        ]);

        $response->assertRedirect(route('leave.types.index'));
        $this->assertDatabaseHas('leave_types', ['id' => $leaveType->id, 'default_days_per_year' => 8]);
    }

    public function test_destroying_a_leave_type_archives_it_instead_of_deleting(): void
    {
        $actor = $this->actorWithPermissions(['leave.manage']);
        $leaveType = LeaveType::create(['name' => 'Casual', 'default_days_per_year' => 6, 'is_active' => true]);

        $response = $this->actingAs($actor)->delete("/leave/types/{$leaveType->id}");

        $response->assertRedirect(route('leave.types.index'));
        $this->assertDatabaseHas('leave_types', ['id' => $leaveType->id, 'is_active' => false]);
    }
}
