<?php

namespace Tests\Feature;

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

    public function test_login_page_is_rendered_by_the_react_login_component(): void
    {
        $response = $this->get('/login');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page->component('Auth/Login'));
    }

    public function test_inertia_login_does_a_full_page_load_to_the_blade_dashboard(): void
    {
        $role = Role::create([
            'name' => 'user',
        ]);

        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => 'Password123!',
            'role_id' => $role->id,
        ]);

        // The dashboard is still Blade, so an Inertia XHR must be told to do
        // a hard visit (409 + X-Inertia-Location), not handed a redirect.
        $response = $this->withHeaders(['X-Inertia' => 'true'])->post('/login', [
            'email' => 'test@example.com',
            'password' => 'Password123!',
        ]);

        $response->assertStatus(409);
        $response->assertHeader('X-Inertia-Location', '/dashboard');

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

        $response->assertStatus(200);
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

        $response = $this->post('/logout');

        $response->assertRedirect();

        $this->assertGuest();
    }
}
