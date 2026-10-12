<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
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

    public function test_deleting_a_user_removes_them_and_revokes_their_api_tokens(): void
    {
        $admin = $this->actorWithPermissions('admin', ['users.view', 'users.delete']);
        $userRole = Role::firstOrCreate(['name' => 'user'], ['level' => 10]);
        $target = User::factory()->create(['role_id' => $userRole->id]);
        $target->createToken('api-token');

        $response = $this->actingAs($admin)->delete("/users/{$target->id}");

        $response->assertRedirect(route('users.index'));

        $this->assertDatabaseMissing('users', ['id' => $target->id]);
        $this->assertDatabaseMissing('personal_access_tokens', ['tokenable_id' => $target->id]);
    }

    public function test_deleted_users_email_can_be_reused(): void
    {
        $admin = $this->actorWithPermissions('admin', ['users.view', 'users.delete', 'users.create']);
        $userRole = Role::firstOrCreate(['name' => 'user'], ['level' => 10]);
        $target = User::factory()->create(['role_id' => $userRole->id, 'email' => 'reusable@example.com']);

        $this->actingAs($admin)->delete("/users/{$target->id}");

        $response = $this->actingAs($admin)->post('/users', [
            'name' => 'New Owner',
            'email' => 'reusable@example.com',
            'role_id' => $userRole->id,
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('users', ['name' => 'New Owner', 'email' => 'reusable@example.com']);
    }

    public function test_creating_a_user_forces_a_password_change(): void
    {
        $admin = $this->actorWithPermissions('admin', ['users.view', 'users.create']);
        $userRole = Role::firstOrCreate(['name' => 'user'], ['level' => 10]);

        $this->actingAs($admin)->post('/users', [
            'name' => 'Fresh Hire',
            'email' => 'fresh.hire@example.com',
            'role_id' => $userRole->id,
        ]);

        $created = User::where('email', 'fresh.hire@example.com')->firstOrFail();

        $this->assertTrue($created->must_change_password);
        $this->assertSame($userRole->id, $created->role_id);
    }

    public function test_updating_a_user_persists_changes(): void
    {
        $admin = $this->actorWithPermissions('admin', ['users.view', 'users.update']);
        $userRole = Role::firstOrCreate(['name' => 'user'], ['level' => 10]);
        $target = User::factory()->create(['role_id' => $userRole->id, 'name' => 'Old Name']);

        $response = $this->actingAs($admin)->put("/users/{$target->id}", [
            'name' => 'New Name',
            'email' => $target->email,
            'role_id' => $userRole->id,
        ]);

        $response->assertRedirect(route('users.index'));
        $this->assertSame('New Name', $target->fresh()->name);
    }

    public function test_create_page_is_react_and_offers_only_roles_the_actor_may_create(): void
    {
        // A manager may create a peer manager or a user (UserPolicy::create),
        // never an admin.
        Role::firstOrCreate(['name' => 'admin'], ['level' => 100]);
        $managerRole = Role::firstOrCreate(['name' => 'manager'], ['level' => 50]);
        $userRole = Role::firstOrCreate(['name' => 'user'], ['level' => 10]);
        $manager = $this->actorWithPermissions('manager', ['users.create']);

        $this->actingAs($manager)
            ->get('/users/create')
            ->assertInertia(fn (Assert $page) => $page
                ->component('Users/Create')
                ->where('roles', [
                    ['id' => $managerRole->id, 'name' => 'manager'],
                    ['id' => $userRole->id, 'name' => 'user'],
                ])
                ->where('createdUser', null));
    }

    public function test_created_user_credentials_are_shown_once_then_gone(): void
    {
        $admin = $this->actorWithPermissions('admin', ['users.create']);
        $userRole = Role::firstOrCreate(['name' => 'user'], ['level' => 10]);

        $this->actingAs($admin)
            ->post('/users', ['name' => 'Jane Doe', 'email' => 'jane@example.com', 'role_id' => $userRole->id])
            ->assertRedirect(route('users.create'));

        $this->get('/users/create')
            ->assertInertia(fn (Assert $page) => $page
                ->component('Users/Create')
                ->where('createdUser.email', 'jane@example.com')
                ->where('createdUser.role', 'user')
                ->has('createdUser.password'));

        $this->get('/users/create')
            ->assertInertia(fn (Assert $page) => $page->where('createdUser', null));
    }

    public function test_edit_page_is_react_with_the_users_current_values(): void
    {
        $admin = $this->actorWithPermissions('admin', ['users.update']);
        $userRole = Role::firstOrCreate(['name' => 'user'], ['level' => 10]);
        $auditor = Role::create(['name' => 'Auditor', 'type' => 'functional', 'level' => 0]);
        $target = User::factory()->create(['role_id' => $userRole->id]);
        $target->functionalRoles()->attach($auditor);

        $this->actingAs($admin)
            ->get("/users/{$target->id}/edit")
            ->assertInertia(fn (Assert $page) => $page
                ->component('Users/Edit')
                ->where('user.id', $target->id)
                ->where('user.email', $target->email)
                ->where('user.role_id', $userRole->id)
                ->where('user.functional_role_ids', [$auditor->id])
                ->where('functionalRoles', [['id' => $auditor->id, 'name' => 'Auditor']])
                ->where('canAssignFunctionalRoles', true));
    }

    public function test_manager_edit_page_offers_only_strictly_lower_roles(): void
    {
        Role::firstOrCreate(['name' => 'manager'], ['level' => 50]);
        $userRole = Role::firstOrCreate(['name' => 'user'], ['level' => 10]);
        $manager = $this->actorWithPermissions('manager', ['users.update']);
        $target = User::factory()->create(['role_id' => $userRole->id]);

        $this->actingAs($manager)
            ->get("/users/{$target->id}/edit")
            ->assertInertia(fn (Assert $page) => $page
                ->component('Users/Edit')
                ->where('roles', [['id' => $userRole->id, 'name' => 'user']]));
    }
}
