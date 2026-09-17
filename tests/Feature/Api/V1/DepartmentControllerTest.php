<?php

namespace Tests\Feature\Api\V1;

use App\Models\Department;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepartmentControllerTest extends TestCase
{
    use RefreshDatabase;

    private function actorWithPermissions(array $permissionNames): User
    {
        $role = Role::firstOrCreate(['name' => 'admin'], ['type' => 'hierarchy', 'level' => 100]);

        foreach ($permissionNames as $name) {
            $permission = Permission::firstOrCreate(['name' => $name]);
            $role->permissions()->syncWithoutDetaching($permission);
        }

        return User::factory()->create(['role_id' => $role->id]);
    }

    public function test_guest_cannot_list_departments(): void
    {
        $response = $this->getJson('/api/v1/departments');

        $response->assertStatus(401);
    }

    public function test_actor_with_departments_view_permission_can_list_departments(): void
    {
        $actor = $this->actorWithPermissions(['departments.view']);
        Department::create(['name' => 'Engineering']);

        $response = $this->actingAs($actor)->getJson('/api/v1/departments');

        $response->assertOk();
        $response->assertJsonFragment(['name' => 'Engineering']);
        // Small, unpaginated catalog, like Roles/Permissions — not the
        // Users-style paginated envelope.
        $response->assertJsonStructure(['data']);
        $response->assertJsonMissingPath('meta');
        $response->assertJsonMissingPath('links');
    }

    public function test_actor_without_departments_view_permission_cannot_list_departments(): void
    {
        $actor = $this->actorWithPermissions([]);

        $response = $this->actingAs($actor)->getJson('/api/v1/departments');

        $response->assertStatus(403);
    }

    public function test_show_returns_a_single_department_with_head_and_user_count(): void
    {
        $actor = $this->actorWithPermissions(['departments.view']);
        $headRole = Role::create(['name' => 'manager', 'type' => 'hierarchy', 'level' => 50]);
        $head = User::factory()->create(['role_id' => $headRole->id]);
        $department = Department::create(['name' => 'Sales', 'head_user_id' => $head->id]);

        $userRole = Role::create(['name' => 'user', 'type' => 'hierarchy', 'level' => 10]);
        User::factory()->create(['role_id' => $userRole->id, 'department_id' => $department->id]);

        $response = $this->actingAs($actor)->getJson("/api/v1/departments/{$department->id}");

        $response->assertOk();
        $response->assertJsonPath('data.name', 'Sales');
        $response->assertJsonPath('data.head.id', $head->id);
        $response->assertJsonPath('data.users_count', 1);
    }

    public function test_actor_without_departments_view_permission_cannot_view_a_department(): void
    {
        $actor = $this->actorWithPermissions([]);
        $department = Department::create(['name' => 'Sales']);

        $response = $this->actingAs($actor)->getJson("/api/v1/departments/{$department->id}");

        $response->assertStatus(403);
    }

    public function test_actor_with_departments_view_permission_can_list_a_departments_users(): void
    {
        $actor = $this->actorWithPermissions(['departments.view']);
        $department = Department::create(['name' => 'Engineering']);

        $userRole = Role::create(['name' => 'user', 'type' => 'hierarchy', 'level' => 10]);
        $member = User::factory()->create(['role_id' => $userRole->id, 'department_id' => $department->id]);

        $response = $this->actingAs($actor)->getJson("/api/v1/departments/{$department->id}/users");

        $response->assertOk();
        $response->assertJsonFragment(['id' => $member->id, 'name' => $member->name]);
        // Paginated, like GET /users.
        $response->assertJsonStructure(['data', 'links', 'meta']);
    }

    public function test_departments_users_endpoint_only_returns_members_of_that_department(): void
    {
        $actor = $this->actorWithPermissions(['departments.view']);
        $engineering = Department::create(['name' => 'Engineering']);
        $sales = Department::create(['name' => 'Sales']);

        $userRole = Role::create(['name' => 'user', 'type' => 'hierarchy', 'level' => 10]);
        $engineer = User::factory()->create(['role_id' => $userRole->id, 'department_id' => $engineering->id]);
        User::factory()->create(['role_id' => $userRole->id, 'department_id' => $sales->id]);

        $response = $this->actingAs($actor)->getJson("/api/v1/departments/{$engineering->id}/users");

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonFragment(['id' => $engineer->id]);
    }

    public function test_actor_without_departments_view_permission_cannot_list_a_departments_users(): void
    {
        $actor = $this->actorWithPermissions([]);
        $department = Department::create(['name' => 'Engineering']);

        $response = $this->actingAs($actor)->getJson("/api/v1/departments/{$department->id}/users");

        $response->assertStatus(403);
    }
}
