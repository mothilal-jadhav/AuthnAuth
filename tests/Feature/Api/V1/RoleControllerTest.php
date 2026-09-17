<?php

namespace Tests\Feature\Api\V1;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleControllerTest extends TestCase
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

    public function test_guest_cannot_list_roles(): void
    {
        $response = $this->getJson('/api/v1/roles');

        $response->assertStatus(401);
    }

    public function test_actor_with_roles_view_permission_can_list_roles(): void
    {
        $actor = $this->actorWithPermissions(['roles.view']);
        Role::create(['name' => 'Payroll Officer', 'type' => 'functional', 'level' => 0]);

        $response = $this->actingAs($actor)->getJson('/api/v1/roles');

        $response->assertOk();
        $response->assertJsonFragment(['name' => 'Payroll Officer', 'type' => 'functional']);
        // Roles/Permissions are small, unpaginated catalogs (unlike /users),
        // so no meta/links keys here — a deliberately simpler envelope than
        // the paginated endpoints, not an inconsistency.
        $response->assertJsonStructure(['data']);
        $response->assertJsonMissingPath('meta');
        $response->assertJsonMissingPath('links');
    }

    public function test_actor_without_roles_view_permission_cannot_list_roles(): void
    {
        $actor = $this->actorWithPermissions([]);

        $response = $this->actingAs($actor)->getJson('/api/v1/roles');

        $response->assertStatus(403);
    }

    public function test_show_returns_a_single_role(): void
    {
        $actor = $this->actorWithPermissions(['roles.view']);
        $role = Role::create(['name' => 'Recruiter', 'type' => 'functional', 'level' => 0]);

        $response = $this->actingAs($actor)->getJson("/api/v1/roles/{$role->id}");

        $response->assertOk();
        $response->assertJsonPath('data.name', 'Recruiter');
        $response->assertJsonPath('data.type', 'functional');
    }
}
