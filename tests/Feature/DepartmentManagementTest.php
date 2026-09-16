<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepartmentManagementTest extends TestCase
{
    use RefreshDatabase;

    private function actorWithPermissions(array $permissions): User
    {
        $role = Role::create(['name' => 'manager', 'type' => 'hierarchy', 'level' => 50]);

        foreach ($permissions as $name) {
            $permission = Permission::create(['name' => $name]);
            $role->permissions()->attach($permission);
        }

        return User::factory()->create(['role_id' => $role->id]);
    }

    public function test_guest_cannot_access_departments_page(): void
    {
        $response = $this->get('/departments');

        $response->assertRedirect('/login');
    }

    public function test_user_with_departments_view_permission_can_access_departments_page(): void
    {
        $actor = $this->actorWithPermissions(['departments.view']);

        $response = $this->actingAs($actor)->get('/departments');

        $response->assertStatus(200);
    }

    public function test_user_without_departments_view_permission_cannot_access_departments_page(): void
    {
        $actor = $this->actorWithPermissions([]);

        $response = $this->actingAs($actor)->get('/departments');

        $response->assertStatus(403);
    }

    public function test_user_with_departments_create_permission_can_create_department(): void
    {
        $actor = $this->actorWithPermissions(['departments.create']);

        $response = $this->actingAs($actor)->post('/departments', [
            'name' => 'Engineering',
        ]);

        $response->assertRedirect(route('departments.index'));

        $this->assertDatabaseHas('departments', ['name' => 'Engineering']);
    }

    public function test_user_without_departments_create_permission_cannot_create_department(): void
    {
        $actor = $this->actorWithPermissions(['departments.view']);

        $response = $this->actingAs($actor)->post('/departments', [
            'name' => 'Engineering',
        ]);

        $response->assertStatus(403);

        $this->assertDatabaseMissing('departments', ['name' => 'Engineering']);
    }

    public function test_user_with_departments_update_permission_can_update_department(): void
    {
        $actor = $this->actorWithPermissions(['departments.update']);
        $department = Department::create(['name' => 'Old Name']);

        $response = $this->actingAs($actor)->put("/departments/{$department->id}", [
            'name' => 'New Name',
        ]);

        $response->assertRedirect(route('departments.index'));

        $this->assertDatabaseHas('departments', ['id' => $department->id, 'name' => 'New Name']);
    }

    public function test_user_with_departments_delete_permission_can_delete_department(): void
    {
        $actor = $this->actorWithPermissions(['departments.delete']);
        $department = Department::create(['name' => 'Sales']);

        $response = $this->actingAs($actor)->delete("/departments/{$department->id}");

        $response->assertRedirect(route('departments.index'));

        $this->assertDatabaseMissing('departments', ['id' => $department->id]);
    }

    public function test_deleting_a_department_nulls_out_its_members_department_id(): void
    {
        $actor = $this->actorWithPermissions(['departments.delete']);
        $department = Department::create(['name' => 'Finance']);

        $userRole = Role::create(['name' => 'user', 'type' => 'hierarchy', 'level' => 10]);
        $member = User::factory()->create(['role_id' => $userRole->id, 'department_id' => $department->id]);

        $this->actingAs($actor)->delete("/departments/{$department->id}");

        $this->assertDatabaseHas('users', ['id' => $member->id, 'department_id' => null]);
    }
}
