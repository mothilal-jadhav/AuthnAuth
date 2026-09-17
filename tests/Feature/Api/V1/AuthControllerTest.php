<?php

namespace Tests\Feature\Api\V1;

use App\Http\Controllers\Api\V1\AuthController;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Laravel\Sanctum\TransientToken;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Deliberately does NOT use $this->actingAs() anywhere in this file.
     * actingAs() sets the user directly on the 'web' guard in-process, and
     * Sanctum's guard falls back to whatever the 'web' guard already
     * resolved — so actingAs()-based tests would "pass" even if the real
     * bearer-token HTTP flow were broken (this is exactly how the earlier
     * session-vs-api-group gap slipped past the rest of the suite). Every
     * test here goes through a real POST /auth/login and a real
     * Authorization header, the way an actual API client would.
     */
    private function makeUser(array $attributes = []): User
    {
        $role = Role::firstOrCreate(['name' => 'user'], ['type' => 'hierarchy', 'level' => 10]);

        return User::factory()->create(array_merge(['role_id' => $role->id], $attributes));
    }

    public function test_user_can_login_and_receive_a_token(): void
    {
        $user = $this->makeUser(['email' => 'login@example.com', 'password' => 'Password123!']);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'login@example.com',
            'password' => 'Password123!',
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.email', 'login@example.com');
        $response->assertJsonStructure(['meta' => ['token']]);
        $this->assertNotEmpty($response->json('meta.token'));
    }

    public function test_login_fails_with_invalid_password(): void
    {
        $this->makeUser(['email' => 'login@example.com', 'password' => 'Password123!']);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'login@example.com',
            'password' => 'WrongPassword!',
        ]);

        $response->assertStatus(401);
        $response->assertJsonPath('message', 'The provided credentials do not match our records.');
    }

    public function test_login_fails_for_a_nonexistent_email_with_the_same_generic_message(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'nobody@example.com',
            'password' => 'WhateverPass1!',
        ]);

        $response->assertStatus(401);
        $response->assertJsonPath('message', 'The provided credentials do not match our records.');
    }

    public function test_login_blocks_a_user_who_must_change_their_password(): void
    {
        $this->makeUser([
            'email' => 'temp@example.com',
            'password' => 'TempPass123!',
            'must_change_password' => true,
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'temp@example.com',
            'password' => 'TempPass123!',
        ]);

        $response->assertStatus(423);
        $response->assertJsonPath('error', 'password_change_required');
    }

    public function test_a_real_token_from_login_authenticates_subsequent_requests(): void
    {
        $this->makeUser(['email' => 'flow@example.com', 'password' => 'Password123!']);

        $login = $this->postJson('/api/v1/auth/login', [
            'email' => 'flow@example.com',
            'password' => 'Password123!',
        ]);
        $token = $login->json('meta.token');

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/auth/me');

        $response->assertOk();
        $response->assertJsonPath('data.email', 'flow@example.com');
    }

    public function test_guest_cannot_access_me(): void
    {
        $response = $this->getJson('/api/v1/auth/me');

        $response->assertStatus(401);
    }

    public function test_an_invalid_bearer_token_is_rejected(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer not-a-real-token')
            ->getJson('/api/v1/auth/me');

        $response->assertStatus(401);
    }

    public function test_logout_revokes_the_token_so_it_no_longer_works(): void
    {
        $this->makeUser(['email' => 'logout@example.com', 'password' => 'Password123!']);

        $login = $this->postJson('/api/v1/auth/login', [
            'email' => 'logout@example.com',
            'password' => 'Password123!',
        ]);
        $token = $login->json('meta.token');

        $logoutResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/auth/logout');
        $logoutResponse->assertNoContent();

        // Sanctum's guard caches its resolved user for the lifetime of the
        // guard instance, which (unlike a real request) persists across
        // multiple simulated requests within one test method — without
        // this, the next call below would read the cached pre-logout user
        // instead of re-resolving the (now-deleted) token from the DB.
        $this->app['auth']->forgetGuards();

        $meResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/auth/me');
        $meResponse->assertStatus(401);
    }

    public function test_api_login_is_rate_limited(): void
    {
        $this->makeUser(['email' => 'throttle@example.com', 'password' => 'Password123!']);

        for ($i = 0; $i < 6; $i++) {
            $this->postJson('/api/v1/auth/login', [
                'email' => 'throttle@example.com',
                'password' => 'WrongPassword!',
            ]);
        }

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'throttle@example.com',
            'password' => 'WrongPassword!',
        ]);

        $response->assertStatus(429);
    }

    public function test_a_token_older_than_the_configured_expiration_is_rejected(): void
    {
        // Security review finding #2: tokens must not be valid indefinitely.
        // Functional proof, not just a config-value check — Sanctum enforces
        // this as a rolling max-age from the token's created_at (see
        // Laravel\Sanctum\Guard::isValidAccessToken()), not a stored
        // expires_at, so this has to be proven by traveling past that
        // window and confirming the token stops working.
        $this->makeUser(['email' => 'expiry@example.com', 'password' => 'Password123!']);

        $login = $this->postJson('/api/v1/auth/login', [
            'email' => 'expiry@example.com',
            'password' => 'Password123!',
        ]);
        $token = $login->json('meta.token');

        $stillValid = $this->withHeader('Authorization', "Bearer {$token}")->getJson('/api/v1/auth/me');
        $stillValid->assertOk();

        // Sanctum's RequestGuard caches its resolved user for the guard
        // instance's lifetime, same issue as in test_logout_revokes_...
        // above — without this, the next call would reuse the cached
        // pre-travel resolution instead of re-validating against the
        // (now expired) token.
        $this->app['auth']->forgetGuards();

        $this->travel(config('sanctum.expiration') + 1)->minutes();

        $expired = $this->withHeader('Authorization', "Bearer {$token}")->getJson('/api/v1/auth/me');
        $expired->assertStatus(401);
    }

    public function test_logout_does_not_crash_if_the_sanctum_session_fallback_ever_activates(): void
    {
        // Security review finding #4: auth:sanctum checks the 'web' session
        // guard before the bearer token (Laravel\Sanctum\Guard::__invoke()),
        // attaching a TransientToken — which has no delete() method — if
        // that fallback ever resolves a user. Dormant today only because
        // routes/api.php has no session middleware; calling the controller
        // directly is the only way to exercise this path without actually
        // reconfiguring middleware, and proves logout() degrades to a
        // clean no-op instead of a fatal error if that ever changes.
        $role = Role::firstOrCreate(['name' => 'user'], ['type' => 'hierarchy', 'level' => 10]);
        $user = User::factory()->create(['role_id' => $role->id])
            ->withAccessToken(new TransientToken);

        $request = Request::create('/api/v1/auth/logout', 'POST');
        $request->setUserResolver(fn () => $user);

        $response = (new AuthController)->logout($request);

        $this->assertSame(204, $response->getStatusCode());
    }
}
