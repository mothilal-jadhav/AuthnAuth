<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_users_page(): void
    {
        $response = $this->get('/users');

        $response->assertRedirect('/login');
    }

    public function test_user_with_users_view_permission_can_access_users_page(): void
    {
        $role = Role::create([
            'name' => 'manager',
        ]);

        $permission = Permission::create([
            'name' => 'users.view',
        ]);

        $role->permissions()->attach($permission);

        $user = User::factory()->create([
            'role_id' => $role->id,
        ]);

        $response = $this
            ->actingAs($user)
            ->get('/users');

        $response->assertStatus(200);
    }

    public function test_user_without_users_view_permission_cannot_access_users_page(): void
    {
        $role = Role::create([
            'name' => 'user',
        ]);

        $user = User::factory()->create([
            'role_id' => $role->id,
        ]);

        $response = $this
            ->actingAs($user)
            ->get('/users');

        $response->assertStatus(403);
    }

    public function test_user_with_users_create_permission_can_access_create_page(): void
    {
        $role = Role::create([
            'name' => 'admin',
        ]);

        $permission = Permission::create([
            'name' => 'users.create',
        ]);

        $role->permissions()->attach($permission);

        $user = User::factory()->create([
            'role_id' => $role->id,
        ]);

        $response = $this
            ->actingAs($user)
            ->get('/users/create');

        $response->assertStatus(200);
    }

    public function test_user_without_users_create_permission_cannot_access_create_page(): void
    {
        $role = Role::create([
            'name' => 'manager',
        ]);

        $permission = Permission::create([
            'name' => 'users.view',
        ]);

        $role->permissions()->attach($permission);

        $user = User::factory()->create([
            'role_id' => $role->id,
        ]);

        $response = $this
            ->actingAs($user)
            ->get('/users/create');

        $response->assertStatus(403);
    }

    public function test_user_with_users_create_permission_can_create_user(): void
    {
        $adminRole = Role::create([
            'name' => 'admin',
        ]);

        $createPermission = Permission::create([
            'name' => 'users.create',
        ]);

        $adminRole->permissions()->attach($createPermission);

        $userRole = Role::create([
            'name' => 'user',
        ]);

        $admin = User::factory()->create([
            'role_id' => $adminRole->id,
        ]);

        $response = $this
            ->actingAs($admin)
            ->post('/users', [
                'name' => 'Test Employee',
                'email' => 'employee@example.com',
                'role_id' => $userRole->id,
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('users', [
            'name' => 'Test Employee',
            'email' => 'employee@example.com',
            'role_id' => $userRole->id,
        ]);
    }

    public function test_user_without_users_create_permission_cannot_create_user(): void
    {
        $managerRole = Role::create([
            'name' => 'manager',
        ]);

        $viewPermission = Permission::create([
            'name' => 'users.view',
        ]);

        $managerRole->permissions()->attach($viewPermission);

        $userRole = Role::create([
            'name' => 'user',
        ]);

        $manager = User::factory()->create([
            'role_id' => $managerRole->id,
        ]);

        $response = $this
            ->actingAs($manager)
            ->post('/users', [
                'name' => 'Unauthorized User',
                'email' => 'unauthorized@example.com',
                'role_id' => $userRole->id,
            ]);

        $response->assertStatus(403);

        $this->assertDatabaseMissing('users', [
            'email' => 'unauthorized@example.com',
        ]);
    }
}
