<?php

namespace Tests\Feature\Api\V1;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\Support\EnforcingCsrf;
use Tests\TestCase;

/**
 * Browser-session (Sanctum SPA) access to the API, enabled by statefulApi()
 * in bootstrap/app.php.
 *
 * Like AuthControllerTest, deliberately never uses actingAs(): it sets the
 * user on the 'web' guard in-process, which Sanctum's guard checks first, so
 * it would pass whether or not session auth actually works over HTTP. Even
 * a real POST /login leaves the user cached in-process — on the guard
 * instance and in the `session.store` singleton (the array driver keeps its
 * data there too) — so every test logs in for real, then drops both and
 * replays only the session cookie against the database session driver: the
 * user has to be recovered from that cookie, as in a browser.
 */
class SessionAuthTest extends TestCase
{
    use RefreshDatabase;

    private const FRONTEND = 'http://app.test';

    protected function setUp(): void
    {
        parent::setUp();

        // Pinned rather than relying on the env-derived default list, so the
        // stateful/non-stateful split below doesn't depend on APP_URL.
        config(['sanctum.stateful' => ['app.test']]);

        // Persisted outside the process, unlike phpunit.xml's array driver,
        // so forgetInProcessAuthState() can't also throw the session away.
        config(['session.driver' => 'database']);
    }

    private function forgetInProcessAuthState(): void
    {
        $this->app['auth']->forgetGuards();
        $this->app['session']->forgetDrivers();
        $this->app->forgetInstance('session.store');
    }

    private function makeUser(string $roleName, array $permissions = [], array $attributes = []): User
    {
        $level = ['admin' => 100, 'manager' => 50][$roleName] ?? 10;
        $role = Role::firstOrCreate(['name' => $roleName], ['type' => 'hierarchy', 'level' => $level]);

        foreach ($permissions as $name) {
            $role->permissions()->syncWithoutDetaching(Permission::firstOrCreate(['name' => $name]));
        }

        return User::factory()->create(array_merge([
            'role_id' => $role->id,
            'password' => 'Password123!',
        ], $attributes));
    }

    /**
     * Real web login. Returns the response so callers can read both the
     * session cookie and the XSRF-TOKEN cookie it set.
     */
    private function loginViaWeb(User $user): TestResponse
    {
        $response = $this->post('/login', ['email' => $user->email, 'password' => 'Password123!']);
        $response->assertRedirect('/dashboard');

        $this->forgetInProcessAuthState();

        return $response;
    }

    private function fromBrowserSession(TestResponse $login, bool $fromFrontend = true): static
    {
        $cookie = config('session.cookie');
        // withCredentials(): the *Json() helpers otherwise drop cookies
        // entirely, unlike a same-origin browser fetch().
        $request = $this->withCredentials()->withCookie($cookie, $login->getCookie($cookie)->getValue());

        return $fromFrontend ? $request->withHeader('Referer', self::FRONTEND.'/users') : $request;
    }

    public function test_session_cookie_from_a_stateful_frontend_authenticates_the_api(): void
    {
        $admin = $this->makeUser('admin', ['users.view']);
        $login = $this->loginViaWeb($admin);

        $response = $this->fromBrowserSession($login)->getJson('/api/v1/users');

        $response->assertOk();
        $response->assertJsonFragment(['email' => $admin->email]);
    }

    public function test_session_cookie_without_a_frontend_referer_is_not_accepted(): void
    {
        $admin = $this->makeUser('admin', ['users.view']);
        $login = $this->loginViaWeb($admin);

        $this->fromBrowserSession($login, fromFrontend: false)
            ->getJson('/api/v1/users')
            ->assertStatus(401);
    }

    public function test_session_cookie_from_an_unlisted_origin_is_not_accepted(): void
    {
        $admin = $this->makeUser('admin', ['users.view']);
        $login = $this->loginViaWeb($admin);

        $this->fromBrowserSession($login, fromFrontend: false)
            ->withHeader('Referer', 'https://evil.test/')
            ->getJson('/api/v1/users')
            ->assertStatus(401);
    }

    public function test_no_cookie_and_no_token_is_unauthenticated(): void
    {
        $this->withHeader('Referer', self::FRONTEND.'/users')
            ->getJson('/api/v1/users')
            ->assertStatus(401);
    }

    public function test_session_permissions_are_still_enforced(): void
    {
        $user = $this->makeUser('user');
        $login = $this->loginViaWeb($user);

        $this->fromBrowserSession($login)->getJson('/api/v1/users')->assertStatus(403);
    }

    public function test_session_user_forced_to_change_password_gets_423(): void
    {
        $admin = $this->makeUser('admin', ['users.view']);
        $login = $this->loginViaWeb($admin);

        $admin->forceFill(['must_change_password' => true])->save();

        $this->fromBrowserSession($login)->getJson('/api/v1/users')->assertStatus(423);
    }

    public function test_api_logout_is_a_no_op_for_a_session_caller(): void
    {
        // Documents AuthController::logout(): it only revokes bearer tokens.
        // A browser session must log out via the web POST /logout instead.
        $admin = $this->makeUser('admin', ['users.view']);
        $login = $this->loginViaWeb($admin);

        $this->fromBrowserSession($login)->postJson('/api/v1/auth/logout')->assertNoContent();

        $this->forgetInProcessAuthState();

        $this->fromBrowserSession($login)->getJson('/api/v1/users')->assertOk();
    }

    public function test_session_delete_without_a_csrf_token_is_rejected(): void
    {
        config(['sanctum.middleware.validate_csrf_token' => EnforcingCsrf::class]);

        $admin = $this->makeUser('admin', ['users.delete']);
        $target = $this->makeUser('user');
        $login = $this->loginViaWeb($admin);

        $this->fromBrowserSession($login)->deleteJson("/api/v1/users/{$target->id}")->assertStatus(419);

        $this->assertModelExists($target);
    }

    public function test_session_delete_from_a_cross_site_context_is_rejected(): void
    {
        config(['sanctum.middleware.validate_csrf_token' => EnforcingCsrf::class]);

        $admin = $this->makeUser('admin', ['users.delete']);
        $target = $this->makeUser('user');
        $login = $this->loginViaWeb($admin);

        $this->fromBrowserSession($login)
            ->withHeader('Sec-Fetch-Site', 'cross-site')
            ->deleteJson("/api/v1/users/{$target->id}")
            ->assertStatus(419);

        $this->assertModelExists($target);
    }

    public function test_session_delete_with_the_xsrf_token_succeeds(): void
    {
        config(['sanctum.middleware.validate_csrf_token' => EnforcingCsrf::class]);

        $admin = $this->makeUser('admin', ['users.delete']);
        $target = $this->makeUser('user');
        $login = $this->loginViaWeb($admin);

        // What the browser sends: the XSRF-TOKEN cookie's raw (still
        // encrypted) value echoed back as a header.
        $xsrf = $login->getCookie('XSRF-TOKEN', decrypt: false)->getValue();

        $this->fromBrowserSession($login)
            ->withHeader('X-XSRF-TOKEN', $xsrf)
            ->deleteJson("/api/v1/users/{$target->id}")
            ->assertNoContent();

        $this->assertModelMissing($target);
    }

    public function test_session_delete_marked_same_origin_by_the_browser_succeeds(): void
    {
        // Laravel 13's PreventRequestForgery::hasValidOrigin() accepts
        // Sec-Fetch-Site: same-origin without a token; browsers set this
        // header themselves and page scripts cannot forge it.
        config(['sanctum.middleware.validate_csrf_token' => EnforcingCsrf::class]);

        $admin = $this->makeUser('admin', ['users.delete']);
        $target = $this->makeUser('user');
        $login = $this->loginViaWeb($admin);

        $this->fromBrowserSession($login)
            ->withHeader('Sec-Fetch-Site', 'same-origin')
            ->deleteJson("/api/v1/users/{$target->id}")
            ->assertNoContent();

        $this->assertModelMissing($target);
    }

    public function test_bearer_token_without_a_referer_needs_no_csrf_token(): void
    {
        config(['sanctum.middleware.validate_csrf_token' => EnforcingCsrf::class]);

        $admin = $this->makeUser('admin', ['users.view', 'users.delete']);
        $target = $this->makeUser('user');
        $token = $admin->createToken('test')->plainTextToken;

        $this->withToken($token)->getJson('/api/v1/users')->assertOk();
        $this->withToken($token)->deleteJson("/api/v1/users/{$target->id}")->assertNoContent();
    }

    public function test_bearer_token_from_a_stateful_referer_is_subject_to_csrf(): void
    {
        // Known behavior change from statefulApi(): a token client whose
        // Referer/Origin matches sanctum.stateful (e.g. a same-host API
        // explorer) is treated as the first-party frontend, so its writes
        // need a CSRF token or Sec-Fetch-Site: same-origin. Reads are fine.
        config(['sanctum.middleware.validate_csrf_token' => EnforcingCsrf::class]);

        $admin = $this->makeUser('admin', ['users.view', 'users.delete']);
        $target = $this->makeUser('user');
        $token = $admin->createToken('test')->plainTextToken;

        $this->withToken($token)->withHeader('Referer', self::FRONTEND.'/docs/api')
            ->getJson('/api/v1/users')->assertOk();

        $this->withToken($token)->withHeader('Referer', self::FRONTEND.'/docs/api')
            ->deleteJson("/api/v1/users/{$target->id}")->assertStatus(419);
    }

    public function test_session_takes_precedence_over_a_bearer_token(): void
    {
        $admin = $this->makeUser('admin', ['users.view']);
        $other = $this->makeUser('user');
        $login = $this->loginViaWeb($admin);

        $this->fromBrowserSession($login)
            ->withToken($other->createToken('test')->plainTextToken)
            ->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('data.email', $admin->email);
    }
}
