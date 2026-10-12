<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_route_no_longer_exists(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(404);
    }

    public function test_landing_page_is_rendered_by_the_react_welcome_component(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Welcome'));
    }

    public function test_login_page_is_rendered_by_the_react_login_component(): void
    {
        $response = $this->get('/login');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page->component('Auth/Login'));
    }

    public function test_inertia_login_redirects_to_the_react_dashboard(): void
    {
        $role = Role::create([
            'name' => 'user',
        ]);

        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => 'Password123!',
            'role_id' => $role->id,
        ]);

        // Both pages are React, so an ordinary Inertia redirect.
        $response = $this->withHeaders(['X-Inertia' => 'true'])->post('/login', [
            'email' => 'test@example.com',
            'password' => 'Password123!',
        ]);

        $response->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($user);
    }

    public function test_user_can_login_with_correct_credentials(): void
    {
        $role = Role::create([
            'name' => 'user',
        ]);

        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => 'Password123!',
            'role_id' => $role->id,
        ]);

        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'Password123!',
        ]);

        $response->assertRedirect();

        $this->assertAuthenticatedAs($user);
    }

    public function test_user_cannot_login_with_invalid_password(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => 'Password123!',
        ]);

        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'WrongPassword!',
        ]);

        $this->assertGuest();

        $response->assertSessionHasErrors();
    }

    public function test_login_fails_for_a_nonexistent_email_with_the_same_generic_message(): void
    {
        $response = $this->post('/login', [
            'email' => 'nobody@example.com',
            'password' => 'WhateverPass1!',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors(['email' => 'The provided credentials do not match our records.']);
    }

    public function test_guest_cannot_access_dashboard(): void
    {
        $response = $this->get('/dashboard');

        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_can_access_dashboard(): void
    {
        $role = Role::create([
            'name' => 'user',
        ]);

        $user = User::factory()->create([
            'role_id' => $role->id,
        ]);

        $response = $this
            ->actingAs($user)
            ->get('/dashboard');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->where('role.name', 'user')
            ->where('can', ['viewUsers' => false, 'createUsers' => false]));
    }

    public function test_dashboard_offers_user_actions_only_with_the_matching_permissions(): void
    {
        $role = Role::create(['name' => 'manager']);
        $role->permissions()->attach(Permission::create(['name' => 'users.view']));

        $user = User::factory()->create(['role_id' => $role->id]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->where('can', ['viewUsers' => true, 'createUsers' => false]));
    }

    public function test_user_with_a_temporary_password_is_sent_to_the_react_change_password_page(): void
    {
        $role = Role::create(['name' => 'user']);
        $user = User::factory()->create(['role_id' => $role->id]);
        $user->forceFill(['must_change_password' => true])->save();

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertRedirect('/password/change');

        $this->get('/password/change')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Auth/ChangePassword'));
    }

    public function test_authenticated_user_can_logout(): void
    {
        $role = Role::create([
            'name' => 'user',
        ]);

        $user = User::factory()->create([
            'role_id' => $role->id,
        ]);

        $this->actingAs($user);

        $this->assertAuthenticated();

        $this->assertSame(url('/logout'), route('logout'));

        $response = $this->post(route('logout'));

        $response->assertRedirect('/');

        $this->assertGuest();
    }
}
