<?php

namespace Tests\Feature\Api\V1;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    private function actorWithPermissions(string $roleName, array $permissionNames, ?int $level = null): User
    {
        $level ??= match ($roleName) {
            'admin' => 100,
            'manager' => 50,
            default => 10,
        };

        $role = Role::firstOrCreate(['name' => $roleName], ['type' => 'hierarchy', 'level' => $level]);

        foreach ($permissionNames as $name) {
            $permission = Permission::firstOrCreate(['name' => $name]);
            $role->permissions()->syncWithoutDetaching($permission);
        }

        return User::factory()->create(['name' => ucfirst($roleName).' Actor', 'role_id' => $role->id]);
    }

    private function hierarchyRole(string $name, int $level): Role
    {
        return Role::firstOrCreate(['name' => $name], ['type' => 'hierarchy', 'level' => $level]);
    }

    public function test_guest_cannot_list_users(): void
    {
        $response = $this->getJson('/api/v1/users');

        $response->assertStatus(401);
        $response->assertJsonPath('message', 'Unauthenticated.');
    }

    public function test_actor_with_users_view_permission_can_list_users(): void
    {
        $admin = $this->actorWithPermissions('admin', ['users.view']);
        $userRole = $this->hierarchyRole('user', 10);
        User::factory()->create(['name' => 'Listed Person', 'role_id' => $userRole->id]);

        $response = $this->actingAs($admin)->getJson('/api/v1/users');

        $response->assertOk();
        $response->assertJsonFragment(['name' => 'Listed Person']);
        $response->assertJsonMissingPath('data.0.password');
        $response->assertJsonMissingPath('data.0.remember_token');
        $response->assertJsonStructure(['data', 'links', 'meta' => ['current_page', 'per_page', 'total']]);
    }

    public function test_actor_without_users_view_permission_cannot_list_users(): void
    {
        $user = $this->actorWithPermissions('user', []);

        $response = $this->actingAs($user)->getJson('/api/v1/users');

        $response->assertStatus(403);
        // Regression test: PermissionMiddleware used to call abort(403) with
        // no message, so JSON clients got {"message": ""}.
        $response->assertJsonPath('message', 'This action is unauthorized.');
    }

    public function test_index_filters_by_role(): void
    {
        $admin = $this->actorWithPermissions('admin', ['users.view']);
        $userRole = $this->hierarchyRole('user', 10);
        $managerRole = $this->hierarchyRole('manager', 50);
        User::factory()->create(['name' => 'A User', 'role_id' => $userRole->id]);
        User::factory()->create(['name' => 'A Manager', 'role_id' => $managerRole->id]);

        $response = $this->actingAs($admin)->getJson('/api/v1/users?role=manager');

        $response->assertOk();
        $response->assertJsonFragment(['name' => 'A Manager']);
        $response->assertJsonMissing(['name' => 'A User']);
    }

    public function test_index_filters_by_name(): void
    {
        $admin = $this->actorWithPermissions('admin', ['users.view']);
        $userRole = $this->hierarchyRole('user', 10);
        User::factory()->create(['name' => 'Jane Cooper', 'role_id' => $userRole->id]);
        User::factory()->create(['name' => 'Sam Dawson', 'role_id' => $userRole->id]);

        $response = $this->actingAs($admin)->getJson('/api/v1/users?name=Coop');

        $response->assertOk();
        $response->assertJsonFragment(['name' => 'Jane Cooper']);
        $response->assertJsonMissing(['name' => 'Sam Dawson']);
    }

    public function test_index_filters_by_email(): void
    {
        $admin = $this->actorWithPermissions('admin', ['users.view']);
        $userRole = $this->hierarchyRole('user', 10);
        User::factory()->create(['name' => 'Findable', 'email' => 'find.me@example.com', 'role_id' => $userRole->id]);
        User::factory()->create(['name' => 'Not Findable', 'email' => 'other@example.com', 'role_id' => $userRole->id]);

        $response = $this->actingAs($admin)->getJson('/api/v1/users?email=find.me');

        $response->assertOk();
        $response->assertJsonFragment(['name' => 'Findable']);
        $response->assertJsonMissing(['name' => 'Not Findable']);
    }

    public function test_index_filters_by_pending_status(): void
    {
        $admin = $this->actorWithPermissions('admin', ['users.view']);
        $userRole = $this->hierarchyRole('user', 10);
        User::factory()->create(['name' => 'Pending Person', 'role_id' => $userRole->id, 'must_change_password' => true]);
        User::factory()->create(['name' => 'Active Person', 'role_id' => $userRole->id, 'must_change_password' => false]);

        $response = $this->actingAs($admin)->getJson('/api/v1/users?status=pending');

        $response->assertOk();
        $response->assertJsonFragment(['name' => 'Pending Person']);
        $response->assertJsonMissing(['name' => 'Active Person']);
    }

    public function test_index_filters_by_active_status(): void
    {
        $admin = $this->actorWithPermissions('admin', ['users.view']);
        $userRole = $this->hierarchyRole('user', 10);
        User::factory()->create(['name' => 'Pending Person', 'role_id' => $userRole->id, 'must_change_password' => true]);
        User::factory()->create(['name' => 'Active Person', 'role_id' => $userRole->id, 'must_change_password' => false]);

        $response = $this->actingAs($admin)->getJson('/api/v1/users?status=active');

        $response->assertOk();
        $response->assertJsonFragment(['name' => 'Active Person']);
        $response->assertJsonMissing(['name' => 'Pending Person']);
    }

    public function test_index_rejects_an_invalid_status_value(): void
    {
        $admin = $this->actorWithPermissions('admin', ['users.view']);

        $response = $this->actingAs($admin)->getJson('/api/v1/users?status=bogus');

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('status');
    }

    public function test_index_sorts_by_a_whitelisted_column_and_direction(): void
    {
        $admin = $this->actorWithPermissions('admin', ['users.view']);
        $userRole = $this->hierarchyRole('user', 10);
        User::factory()->create(['name' => 'Aaron First', 'email' => 'aaa@example.com', 'role_id' => $userRole->id]);
        User::factory()->create(['name' => 'Zach Last', 'email' => 'zzz@example.com', 'role_id' => $userRole->id]);

        // Only asserting the relative order of these two (not the full list,
        // which would also include the admin actor's own randomly-generated
        // email) — ascending email order must put 'aaa@...' before 'zzz@...'.
        $ascending = collect($this->actingAs($admin)->getJson('/api/v1/users?sort=email&direction=asc')->json('data'))
            ->pluck('name')
            ->values();
        $this->assertLessThan($ascending->search('Zach Last'), $ascending->search('Aaron First'));

        $descending = collect($this->actingAs($admin)->getJson('/api/v1/users?sort=email&direction=desc')->json('data'))
            ->pluck('name')
            ->values();
        $this->assertLessThan($descending->search('Aaron First'), $descending->search('Zach Last'));
    }

    public function test_index_rejects_a_non_whitelisted_sort_column(): void
    {
        $admin = $this->actorWithPermissions('admin', ['users.view']);

        $response = $this->actingAs($admin)->getJson('/api/v1/users?sort=password');

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('sort');
    }

    public function test_index_respects_a_custom_per_page(): void
    {
        $admin = $this->actorWithPermissions('admin', ['users.view']);
        $userRole = $this->hierarchyRole('user', 10);
        User::factory()->count(5)->create(['role_id' => $userRole->id]);

        $response = $this->actingAs($admin)->getJson('/api/v1/users?per_page=2');

        $response->assertOk();
        $response->assertJsonCount(2, 'data');
        $response->assertJsonPath('meta.per_page', 2);
    }

    public function test_index_rejects_a_per_page_above_the_cap(): void
    {
        $admin = $this->actorWithPermissions('admin', ['users.view']);

        $response = $this->actingAs($admin)->getJson('/api/v1/users?per_page=101');

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('per_page');
    }

    public function test_actor_with_users_create_permission_can_create_user(): void
    {
        $admin = $this->actorWithPermissions('admin', ['users.view', 'users.create']);
        $userRole = $this->hierarchyRole('user', 10);

        $response = $this->actingAs($admin)->postJson('/api/v1/users', [
            'name' => 'Fresh Hire',
            'email' => 'fresh.hire@example.com',
            'role_id' => $userRole->id,
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.name', 'Fresh Hire');
        $response->assertJsonPath('data.email', 'fresh.hire@example.com');
        $response->assertJsonPath('data.must_change_password', true);
        $response->assertJsonStructure(['meta' => ['temporary_password']]);

        $this->assertArrayNotHasKey('password', $response->json('data'));

        $created = User::where('email', 'fresh.hire@example.com')->firstOrFail();
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'user.created',
            'causer_id' => $admin->id,
            'subject_id' => $created->id,
        ]);
    }

    public function test_actor_without_users_create_permission_cannot_create_user(): void
    {
        $manager = $this->actorWithPermissions('manager', ['users.view']);
        $userRole = $this->hierarchyRole('user', 10);

        $response = $this->actingAs($manager)->postJson('/api/v1/users', [
            'name' => 'Unauthorized',
            'email' => 'unauthorized@example.com',
            'role_id' => $userRole->id,
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseMissing('users', ['email' => 'unauthorized@example.com']);
    }

    public function test_creating_user_with_invalid_role_id_fails_validation(): void
    {
        $admin = $this->actorWithPermissions('admin', ['users.view', 'users.create']);

        $response = $this->actingAs($admin)->postJson('/api/v1/users', [
            'name' => 'Broken Role',
            'email' => 'broken.role@example.com',
            'role_id' => 999999,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('role_id');
    }

    public function test_creating_user_with_duplicate_email_fails_validation(): void
    {
        $admin = $this->actorWithPermissions('admin', ['users.view', 'users.create']);
        $userRole = $this->hierarchyRole('user', 10);
        User::factory()->create(['email' => 'taken@example.com', 'role_id' => $userRole->id]);

        $response = $this->actingAs($admin)->postJson('/api/v1/users', [
            'name' => 'Second Owner',
            'email' => 'taken@example.com',
            'role_id' => $userRole->id,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('email');
    }

    public function test_manager_cannot_create_an_admin_via_api(): void
    {
        $manager = $this->actorWithPermissions('manager', ['users.view', 'users.create']);
        $adminRole = $this->hierarchyRole('admin', 100);

        $response = $this->actingAs($manager)->postJson('/api/v1/users', [
            'name' => 'Escalated Admin',
            'email' => 'escalated.admin@example.com',
            'role_id' => $adminRole->id,
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseMissing('users', ['email' => 'escalated.admin@example.com']);
    }

    public function test_show_returns_user(): void
    {
        $admin = $this->actorWithPermissions('admin', ['users.view']);
        $userRole = $this->hierarchyRole('user', 10);
        $target = User::factory()->create(['name' => 'Viewed Person', 'role_id' => $userRole->id]);

        $response = $this->actingAs($admin)->getJson("/api/v1/users/{$target->id}");

        $response->assertOk();
        $response->assertJsonPath('data.name', 'Viewed Person');
        $response->assertJsonPath('data.role.name', 'user');
        $response->assertJsonMissingPath('data.password');
        $response->assertJsonMissingPath('data.remember_token');
    }

    public function test_show_respects_users_view_permission(): void
    {
        $user = $this->actorWithPermissions('user', []);
        $userRole = $this->hierarchyRole('user', 10);
        $target = User::factory()->create(['role_id' => $userRole->id]);

        $response = $this->actingAs($user)->getJson("/api/v1/users/{$target->id}");

        $response->assertStatus(403);
    }

    public function test_show_returns_404_for_nonexistent_user(): void
    {
        $admin = $this->actorWithPermissions('admin', ['users.view']);

        $response = $this->actingAs($admin)->getJson('/api/v1/users/999999');

        $response->assertStatus(404);
        // Regression test: Laravel's default ModelNotFoundException message
        // leaks the internal model class name (e.g. "No query results for
        // model [App\Models\User] 999999") — bootstrap/app.php now rewrites
        // this to a generic message for JSON responses.
        $response->assertExactJson(['message' => 'Resource not found.']);
    }

    public function test_updating_a_user_persists_changes_and_logs_activity(): void
    {
        $admin = $this->actorWithPermissions('admin', ['users.view', 'users.update']);
        $userRole = $this->hierarchyRole('user', 10);
        $target = User::factory()->create(['name' => 'Old Name', 'role_id' => $userRole->id]);

        $response = $this->actingAs($admin)->putJson("/api/v1/users/{$target->id}", [
            'name' => 'New Name',
            'email' => $target->email,
            'role_id' => $userRole->id,
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.name', 'New Name');

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'user.updated',
            'causer_id' => $admin->id,
            'subject_id' => $target->id,
        ]);
    }

    public function test_update_assigns_functional_role_and_permission_is_immediately_effective(): void
    {
        $admin = $this->actorWithPermissions('admin', ['users.view', 'users.update']);
        $userRole = $this->hierarchyRole('user', 10);
        $payrollOfficer = Role::create(['name' => 'Payroll Officer', 'type' => 'functional', 'level' => 0]);
        $payrollOfficer->permissions()->attach(Permission::firstOrCreate(['name' => 'payroll.view']));
        $target = User::factory()->create(['name' => 'Target Person', 'role_id' => $userRole->id]);

        $this->assertFalse($target->hasPermission('payroll.view'));

        $response = $this->actingAs($admin)->putJson("/api/v1/users/{$target->id}", [
            'name' => $target->name,
            'email' => $target->email,
            'role_id' => $userRole->id,
            'functional_role_ids' => [$payrollOfficer->id],
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.functional_roles.0.name', 'Payroll Officer');

        $this->assertDatabaseHas('role_user', ['user_id' => $target->id, 'role_id' => $payrollOfficer->id]);
        // No manual Cache::forget — the controller must have busted it.
        $this->assertTrue($target->fresh()->hasPermission('payroll.view'));
    }

    public function test_update_with_empty_functional_role_ids_array_clears_functional_roles(): void
    {
        $admin = $this->actorWithPermissions('admin', ['users.view', 'users.update']);
        $userRole = $this->hierarchyRole('user', 10);
        $recruiter = Role::create(['name' => 'Recruiter', 'type' => 'functional', 'level' => 0]);
        $target = User::factory()->create(['name' => 'Target Person', 'role_id' => $userRole->id]);
        $target->functionalRoles()->attach($recruiter);

        $response = $this->actingAs($admin)->putJson("/api/v1/users/{$target->id}", [
            'name' => $target->name,
            'email' => $target->email,
            'role_id' => $userRole->id,
            'functional_role_ids' => [],
        ]);

        $response->assertOk();
        $this->assertDatabaseMissing('role_user', ['user_id' => $target->id, 'role_id' => $recruiter->id]);
    }

    public function test_update_without_functional_role_ids_key_leaves_functional_roles_untouched(): void
    {
        $admin = $this->actorWithPermissions('admin', ['users.view', 'users.update']);
        $userRole = $this->hierarchyRole('user', 10);
        $recruiter = Role::create(['name' => 'Recruiter', 'type' => 'functional', 'level' => 0]);
        $target = User::factory()->create(['role_id' => $userRole->id]);
        $target->functionalRoles()->attach($recruiter);

        $response = $this->actingAs($admin)->putJson("/api/v1/users/{$target->id}", [
            'name' => 'Renamed Only',
            'email' => $target->email,
            'role_id' => $userRole->id,
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('role_user', ['user_id' => $target->id, 'role_id' => $recruiter->id]);
    }

    public function test_manager_cannot_update_themselves(): void
    {
        $manager = $this->actorWithPermissions('manager', ['users.view', 'users.update']);

        $response = $this->actingAs($manager)->putJson("/api/v1/users/{$manager->id}", [
            'name' => 'Self Edit',
            'email' => $manager->email,
            'role_id' => $manager->role_id,
        ]);

        $response->assertStatus(403);
    }

    public function test_manager_cannot_update_a_peer_manager(): void
    {
        $manager = $this->actorWithPermissions('manager', ['users.view', 'users.update']);
        $managerRole = Role::where('name', 'manager')->firstOrFail();
        $peer = User::factory()->create(['name' => 'Peer Manager', 'role_id' => $managerRole->id]);

        $response = $this->actingAs($manager)->putJson("/api/v1/users/{$peer->id}", [
            'name' => 'Renamed Peer',
            'email' => $peer->email,
            'role_id' => $managerRole->id,
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseHas('users', ['id' => $peer->id, 'name' => 'Peer Manager']);
    }

    public function test_manager_cannot_escalate_a_target_users_role_to_admin(): void
    {
        $manager = $this->actorWithPermissions('manager', ['users.view', 'users.update']);
        $userRole = $this->hierarchyRole('user', 10);
        $adminRole = $this->hierarchyRole('admin', 100);
        $target = User::factory()->create(['name' => 'Target Person', 'role_id' => $userRole->id]);

        $response = $this->actingAs($manager)->putJson("/api/v1/users/{$target->id}", [
            'name' => $target->name,
            'email' => $target->email,
            'role_id' => $adminRole->id,
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseHas('users', ['id' => $target->id, 'role_id' => $userRole->id]);
    }

    public function test_deleting_a_user_soft_deletes_and_returns_no_content(): void
    {
        $admin = $this->actorWithPermissions('admin', ['users.view', 'users.delete']);
        $userRole = $this->hierarchyRole('user', 10);
        $target = User::factory()->create(['role_id' => $userRole->id]);

        $response = $this->actingAs($admin)->deleteJson("/api/v1/users/{$target->id}");

        $response->assertNoContent();
        $this->assertSoftDeleted('users', ['id' => $target->id]);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'user.deleted',
            'causer_id' => $admin->id,
            'subject_id' => $target->id,
        ]);
    }

    public function test_last_admin_cannot_be_deleted(): void
    {
        $admin = $this->actorWithPermissions('admin', ['users.view', 'users.delete']);

        $response = $this->actingAs($admin)->deleteJson("/api/v1/users/{$admin->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('users', ['id' => $admin->id, 'deleted_at' => null]);
    }

    public function test_trashed_lists_soft_deleted_users(): void
    {
        $admin = $this->actorWithPermissions('admin', ['users.view', 'users.restore']);
        $userRole = $this->hierarchyRole('user', 10);
        $target = User::factory()->create(['name' => 'Trashed Person', 'role_id' => $userRole->id]);
        $target->delete();

        $response = $this->actingAs($admin)->getJson('/api/v1/users/trashed');

        $response->assertOk();
        $response->assertJsonFragment(['name' => 'Trashed Person']);
    }

    public function test_restore_restores_a_soft_deleted_user(): void
    {
        $admin = $this->actorWithPermissions('admin', ['users.view', 'users.restore']);
        $userRole = $this->hierarchyRole('user', 10);
        $target = User::factory()->create(['role_id' => $userRole->id]);
        $target->delete();

        $response = $this->actingAs($admin)->postJson("/api/v1/users/{$target->id}/restore");

        $response->assertOk();
        $this->assertDatabaseHas('users', ['id' => $target->id, 'deleted_at' => null]);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'user.restored',
            'causer_id' => $admin->id,
            'subject_id' => $target->id,
        ]);
    }

    public function test_actor_without_restore_permission_cannot_restore(): void
    {
        $manager = $this->actorWithPermissions('manager', ['users.view']);
        $userRole = $this->hierarchyRole('user', 10);
        $target = User::factory()->create(['role_id' => $userRole->id]);
        $target->delete();

        $response = $this->actingAs($manager)->postJson("/api/v1/users/{$target->id}/restore");

        $response->assertStatus(403);
        $this->assertSoftDeleted('users', ['id' => $target->id]);
    }

    public function test_restoring_a_nonexistent_user_returns_a_generic_404(): void
    {
        $admin = $this->actorWithPermissions('admin', ['users.view', 'users.restore']);

        $response = $this->actingAs($admin)->postJson('/api/v1/users/999999/restore');

        $response->assertStatus(404);
        // restore()'s findOrFail() is a different code path from implicit
        // route-model binding (used by show()/update()/destroy()) — confirm
        // the same generic-message fix applies here too.
        $response->assertExactJson(['message' => 'Resource not found.']);
    }

    // --- Phase 10 gap-fill: positive hierarchy/authorization coverage,
    // mirroring tests/Unit/Policies/UserPolicyTest.php scenarios but proven
    // through the actual HTTP layer rather than assumed from the unit tests.

    public function test_admin_can_create_another_admin_via_api(): void
    {
        $admin = $this->actorWithPermissions('admin', ['users.view', 'users.create']);
        $adminRole = Role::where('name', 'admin')->firstOrFail();

        $response = $this->actingAs($admin)->postJson('/api/v1/users', [
            'name' => 'Second Admin',
            'email' => 'second.admin@example.com',
            'role_id' => $adminRole->id,
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('users', ['email' => 'second.admin@example.com', 'role_id' => $adminRole->id]);
    }

    public function test_manager_can_create_another_manager_via_api(): void
    {
        // UserPolicy::create() deliberately allows same-level creation (a
        // manager may create a manager), unlike assignRole()'s strict rule —
        // confirming that nuance survives at the HTTP layer, not just in
        // the unit-level policy test.
        $manager = $this->actorWithPermissions('manager', ['users.view', 'users.create']);
        $managerRole = Role::where('name', 'manager')->firstOrFail();

        $response = $this->actingAs($manager)->postJson('/api/v1/users', [
            'name' => 'Second Manager',
            'email' => 'second.manager@example.com',
            'role_id' => $managerRole->id,
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('users', ['email' => 'second.manager@example.com', 'role_id' => $managerRole->id]);
    }

    public function test_manager_can_update_a_lower_level_user(): void
    {
        $manager = $this->actorWithPermissions('manager', ['users.view', 'users.update']);
        $userRole = $this->hierarchyRole('user', 10);
        $target = User::factory()->create(['name' => 'Old Name', 'role_id' => $userRole->id]);

        $response = $this->actingAs($manager)->putJson("/api/v1/users/{$target->id}", [
            'name' => 'New Name',
            'email' => $target->email,
            'role_id' => $userRole->id,
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('users', ['id' => $target->id, 'name' => 'New Name']);
    }

    public function test_manager_can_delete_a_lower_level_user(): void
    {
        $manager = $this->actorWithPermissions('manager', ['users.view', 'users.delete']);
        $userRole = $this->hierarchyRole('user', 10);
        $target = User::factory()->create(['role_id' => $userRole->id]);

        $response = $this->actingAs($manager)->deleteJson("/api/v1/users/{$target->id}");

        $response->assertNoContent();
        $this->assertSoftDeleted('users', ['id' => $target->id]);
    }

    public function test_normal_user_role_is_blocked_by_policy_even_with_explicit_permission(): void
    {
        // Defense-in-depth: UserPolicy::create() hard-blocks role name
        // 'user' from creating anyone, independent of the users.create
        // permission — proving the ordinal-hierarchy layer isn't just
        // "the permission middleware with extra steps."
        $user = $this->actorWithPermissions('user', ['users.create']);
        $targetRole = $this->hierarchyRole('user', 10);

        $response = $this->actingAs($user)->postJson('/api/v1/users', [
            'name' => 'Should Not Exist',
            'email' => 'should.not.exist@example.com',
            'role_id' => $targetRole->id,
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseMissing('users', ['email' => 'should.not.exist@example.com']);
    }

    public function test_admin_can_delete_an_admin_when_another_admin_remains(): void
    {
        $actingAdmin = $this->actorWithPermissions('admin', ['users.view', 'users.delete']);
        $adminRole = Role::where('name', 'admin')->firstOrFail();
        $otherAdmin = User::factory()->create(['name' => 'Other Admin', 'role_id' => $adminRole->id]);

        $response = $this->actingAs($actingAdmin)->deleteJson("/api/v1/users/{$otherAdmin->id}");

        $response->assertNoContent();
        $this->assertSoftDeleted('users', ['id' => $otherAdmin->id]);
    }

    public function test_self_deletion_is_blocked_even_when_not_the_last_admin(): void
    {
        // Isolates the self-management block from the last-admin rule: a
        // second admin exists, so only self-management can be responsible
        // for the block here.
        $admin = $this->actorWithPermissions('admin', ['users.view', 'users.delete']);
        $adminRole = Role::where('name', 'admin')->firstOrFail();
        User::factory()->create(['name' => 'Other Admin', 'role_id' => $adminRole->id]);

        $response = $this->actingAs($admin)->deleteJson("/api/v1/users/{$admin->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('users', ['id' => $admin->id, 'deleted_at' => null]);
    }

    public function test_create_user_requires_name_email_and_role_id(): void
    {
        $admin = $this->actorWithPermissions('admin', ['users.view', 'users.create']);

        $response = $this->actingAs($admin)->postJson('/api/v1/users', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name', 'email', 'role_id']);
    }

    public function test_create_user_rejects_a_malformed_email(): void
    {
        $admin = $this->actorWithPermissions('admin', ['users.view', 'users.create']);
        $userRole = $this->hierarchyRole('user', 10);

        $response = $this->actingAs($admin)->postJson('/api/v1/users', [
            'name' => 'Bad Email',
            'email' => 'not-an-email',
            'role_id' => $userRole->id,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('email');
    }

    public function test_show_with_a_non_numeric_id_returns_a_generic_404(): void
    {
        $admin = $this->actorWithPermissions('admin', ['users.view']);

        $response = $this->actingAs($admin)->getJson('/api/v1/users/not-a-number');

        $response->assertStatus(404);
        $response->assertExactJson(['message' => 'Resource not found.']);
    }

    public function test_unexpected_fields_in_update_payload_have_no_effect(): void
    {
        $admin = $this->actorWithPermissions('admin', ['users.view', 'users.update']);
        $userRole = $this->hierarchyRole('user', 10);
        $target = User::factory()->create(['name' => 'Target Person', 'role_id' => $userRole->id, 'must_change_password' => true]);

        $response = $this->actingAs($admin)->putJson("/api/v1/users/{$target->id}", [
            'name' => $target->name,
            'email' => $target->email,
            'role_id' => $userRole->id,
            // None of these are validated/fillable on update() — confirm
            // they're silently dropped, not silently applied.
            'must_change_password' => false,
            'id' => 999999,
            'password' => 'attempted-takeover',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('users', [
            'id' => $target->id,
            'must_change_password' => true,
        ]);
        $this->assertTrue(Hash::check('password', $target->fresh()->password));
    }

    public function test_protected_routes_advertise_a_rate_limit_via_headers(): void
    {
        $admin = $this->actorWithPermissions('admin', ['users.view']);

        $response = $this->actingAs($admin)->getJson('/api/v1/users');

        $response->assertOk();
        $response->assertHeader('X-RateLimit-Limit', '60');
    }
}
