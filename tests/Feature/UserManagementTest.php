<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    private function actorWithPermissions(string $roleName, array $permissionNames): User
    {
        $role = Role::firstOrCreate(['name' => $roleName], ['level' => $roleName === 'admin' ? 100 : 50]);

        foreach ($permissionNames as $name) {
            $permission = Permission::firstOrCreate(['name' => $name]);
            $role->permissions()->syncWithoutDetaching($permission);
        }

        return User::factory()->create(['role_id' => $role->id]);
    }

    public function test_deleting_a_user_soft_deletes_and_logs_activity(): void
    {
        $admin = $this->actorWithPermissions('admin', ['users.view', 'users.delete']);
        $userRole = Role::firstOrCreate(['name' => 'user'], ['level' => 10]);
        $target = User::factory()->create(['role_id' => $userRole->id]);

        $response = $this->actingAs($admin)->delete("/users/{$target->id}");

        $response->assertRedirect(route('users.index'));

        $this->assertSoftDeleted('users', ['id' => $target->id]);
        $this->assertDatabaseHas('users', ['id' => $target->id]);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'user.deleted',
            'causer_id' => $admin->id,
            'subject_id' => $target->id,
        ]);
    }

    public function test_deleted_user_is_excluded_from_the_users_index(): void
    {
        $admin = $this->actorWithPermissions('admin', ['users.view', 'users.delete']);
        $userRole = Role::firstOrCreate(['name' => 'user'], ['level' => 10]);
        $target = User::factory()->create(['role_id' => $userRole->id, 'name' => 'Soon Gone']);

        $target->delete();

        $response = $this->actingAs($admin)->get('/users');

        $response->assertOk();
        $response->assertDontSee('Soon Gone');
    }

    public function test_admin_can_restore_a_deleted_user(): void
    {
        $admin = $this->actorWithPermissions('admin', ['users.view', 'users.delete', 'users.restore']);
        $userRole = Role::firstOrCreate(['name' => 'user'], ['level' => 10]);
        $target = User::factory()->create(['role_id' => $userRole->id]);
        $target->delete();

        $response = $this->actingAs($admin)->post("/users/{$target->id}/restore");

        $response->assertRedirect(route('users.trashed'));

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
        $userRole = Role::firstOrCreate(['name' => 'user'], ['level' => 10]);
        $target = User::factory()->create(['role_id' => $userRole->id]);
        $target->delete();

        $response = $this->actingAs($manager)->post("/users/{$target->id}/restore");

        $response->assertStatus(403);
        $this->assertSoftDeleted('users', ['id' => $target->id]);
    }

    public function test_guest_cannot_view_trashed_users(): void
    {
        $response = $this->get('/users/trashed');

        $response->assertRedirect('/login');
    }

    public function test_authorized_actor_can_view_trashed_users_list(): void
    {
        $admin = $this->actorWithPermissions('admin', ['users.view', 'users.delete', 'users.restore']);
        $userRole = Role::firstOrCreate(['name' => 'user'], ['level' => 10]);
        $target = User::factory()->create(['role_id' => $userRole->id, 'name' => 'Trashed Person']);
        $target->delete();

        $response = $this->actingAs($admin)->get('/users/trashed');

        $response->assertOk();
        $response->assertSee('Trashed Person');
    }

    public function test_actor_without_restore_permission_cannot_view_trashed_users_list(): void
    {
        $manager = $this->actorWithPermissions('manager', ['users.view']);

        $response = $this->actingAs($manager)->get('/users/trashed');

        $response->assertStatus(403);
    }

    public function test_authorized_actor_can_view_activity_log(): void
    {
        $admin = $this->actorWithPermissions('admin', ['users.view', 'users.create', 'activity.view']);
        $userRole = Role::firstOrCreate(['name' => 'user'], ['level' => 10]);

        $this->actingAs($admin)->post('/users', [
            'name' => 'Logged Hire',
            'email' => 'logged.hire@example.com',
            'role_id' => $userRole->id,
        ]);

        $response = $this->actingAs($admin)->get('/activity');

        $response->assertOk();
        $response->assertSee('user.created');
        $response->assertSee('Logged Hire');
    }

    public function test_actor_without_activity_permission_cannot_view_activity_log(): void
    {
        $manager = $this->actorWithPermissions('manager', ['users.view']);

        $response = $this->actingAs($manager)->get('/activity');

        $response->assertStatus(403);
    }

    public function test_soft_deleted_users_email_still_blocks_reuse(): void
    {
        // Documents a known limitation: MySQL's unique index on `email` is
        // not partial/filtered, so a trashed user's email can't be reused
        // until they're restored. See HasNameEmailRules::emailRules().
        $admin = $this->actorWithPermissions('admin', ['users.view', 'users.delete', 'users.create']);
        $userRole = Role::firstOrCreate(['name' => 'user'], ['level' => 10]);
        $target = User::factory()->create(['role_id' => $userRole->id, 'email' => 'reusable@example.com']);
        $target->delete();

        $response = $this->actingAs($admin)->post('/users', [
            'name' => 'New Owner',
            'email' => 'reusable@example.com',
            'role_id' => $userRole->id,
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertDatabaseMissing('users', ['name' => 'New Owner']);
    }

    public function test_creating_a_user_logs_activity(): void
    {
        $admin = $this->actorWithPermissions('admin', ['users.view', 'users.create']);
        $userRole = Role::firstOrCreate(['name' => 'user'], ['level' => 10]);

        $this->actingAs($admin)->post('/users', [
            'name' => 'Fresh Hire',
            'email' => 'fresh.hire@example.com',
            'role_id' => $userRole->id,
        ]);

        $created = User::where('email', 'fresh.hire@example.com')->firstOrFail();

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'user.created',
            'causer_id' => $admin->id,
            'subject_id' => $created->id,
        ]);
    }

    public function test_updating_a_user_logs_activity_with_changes(): void
    {
        $admin = $this->actorWithPermissions('admin', ['users.view', 'users.update']);
        $userRole = Role::firstOrCreate(['name' => 'user'], ['level' => 10]);
        $target = User::factory()->create(['role_id' => $userRole->id, 'name' => 'Old Name']);

        $this->actingAs($admin)->put("/users/{$target->id}", [
            'name' => 'New Name',
            'email' => $target->email,
            'role_id' => $userRole->id,
        ]);

        $log = ActivityLog::where('action', 'user.updated')
            ->where('subject_id', $target->id)
            ->firstOrFail();

        $this->assertSame($admin->id, $log->causer_id);
        $this->assertSame('New Name', $log->properties['name'] ?? null);
    }
}
