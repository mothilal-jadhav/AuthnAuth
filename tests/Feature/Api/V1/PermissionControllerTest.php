<?php

namespace Tests\Feature\Api\V1;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermissionControllerTest extends TestCase
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

    public function test_guest_cannot_list_permissions(): void
    {
        $response = $this->getJson('/api/v1/permissions');

        $response->assertStatus(401);
    }

    public function test_actor_with_permissions_view_permission_can_list_permissions(): void
    {
        $actor = $this->actorWithPermissions(['permissions.view']);
        Permission::firstOrCreate(['name' => 'payroll.view'], ['group' => 'Payroll']);

        $response = $this->actingAs($actor)->getJson('/api/v1/permissions');

        $response->assertOk();
        $response->assertJsonFragment(['name' => 'payroll.view', 'group' => 'Payroll']);
        // Roles/Permissions are small, unpaginated catalogs (unlike /users),
        // so no meta/links keys here — a deliberately simpler envelope than
        // the paginated endpoints, not an inconsistency.
        $response->assertJsonStructure(['data']);
        $response->assertJsonMissingPath('meta');
        $response->assertJsonMissingPath('links');
    }

    public function test_actor_without_permissions_view_permission_cannot_list_permissions(): void
    {
        $actor = $this->actorWithPermissions([]);

        $response = $this->actingAs($actor)->getJson('/api/v1/permissions');

        $response->assertStatus(403);
    }

    public function test_show_returns_a_single_permission(): void
    {
        $actor = $this->actorWithPermissions(['permissions.view']);
        $permission = Permission::firstOrCreate(['name' => 'leave.approve'], ['group' => 'Leave']);

        $response = $this->actingAs($actor)->getJson("/api/v1/permissions/{$permission->id}");

        $response->assertOk();
        $response->assertJsonPath('data.name', 'leave.approve');
        $response->assertJsonPath('data.group', 'Leave');
    }
}
