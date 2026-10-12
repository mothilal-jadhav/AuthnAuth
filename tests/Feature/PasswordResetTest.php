<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Notifications\PasswordChangedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(): User
    {
        $role = Role::create(['name' => 'user']);

        return User::factory()->create(['role_id' => $role->id]);
    }

    public function test_forgot_password_form_is_accessible(): void
    {
        $response = $this->get('/forgot-password');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Auth/ForgotPassword')
            ->where('status', null));
    }

    public function test_forgot_password_sends_reset_link_for_existing_user(): void
    {
        $user = $this->makeUser();

        $response = $this->post('/forgot-password', [
            'email' => $user->email,
        ]);

        $response->assertSessionHas('status');

        $this->assertDatabaseHas('password_reset_tokens', [
            'email' => $user->email,
        ]);
    }

    public function test_forgot_password_fails_for_unknown_email(): void
    {
        $response = $this->post('/forgot-password', [
            'email' => 'nobody@example.com',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_user_can_reset_password_with_valid_token(): void
    {
        $user = $this->makeUser();

        $token = Password::createToken($user);

        $response = $this->post('/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'NewPassw0rd!',
            'password_confirmation' => 'NewPassw0rd!',
        ]);

        $response->assertRedirect('/login');

        $this->assertTrue(
            Hash::check('NewPassw0rd!', $user->fresh()->password)
        );
    }

    public function test_password_reset_revokes_existing_api_tokens(): void
    {
        // Security review finding #1: this is the account-recovery path,
        // most likely to be used specifically because the account is
        // suspected compromised — a stolen API token must not survive it.
        $user = $this->makeUser();
        $user->createToken('stolen-token');
        $this->assertSame(1, $user->tokens()->count());

        $token = Password::createToken($user);

        $this->post('/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'NewPassw0rd!',
            'password_confirmation' => 'NewPassw0rd!',
        ]);

        $this->assertSame(0, $user->tokens()->count());
    }

    public function test_password_reset_clears_temporary_password_flag_and_notifies_owner(): void
    {
        // Same ChangePassword action as the forced and profile changes: an
        // owner who resets their way out of a temporary password has now
        // chosen their own, so they must not be forced to change it again.
        Notification::fake();

        $user = $this->makeUser();
        $user->forceFill(['must_change_password' => true])->save();

        $this->post('/reset-password', [
            'token' => Password::createToken($user),
            'email' => $user->email,
            'password' => 'NewPassw0rd!',
            'password_confirmation' => 'NewPassw0rd!',
        ])->assertRedirect('/login');

        $this->assertFalse($user->fresh()->must_change_password);
        Notification::assertSentTo($user, PasswordChangedNotification::class);
    }

    public function test_reset_form_is_react_and_prefills_token_and_email(): void
    {
        $this->get('/reset-password/some-token?email=jane%40example.com')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Auth/ResetPassword')
                ->where('token', 'some-token')
                ->where('email', 'jane@example.com'));
    }

    public function test_login_page_shows_the_status_after_a_successful_reset(): void
    {
        $user = $this->makeUser();

        $this->post('/reset-password', [
            'token' => Password::createToken($user),
            'email' => $user->email,
            'password' => 'NewPassw0rd!',
            'password_confirmation' => 'NewPassw0rd!',
        ])->assertRedirect('/login');

        $this->get('/login')
            ->assertInertia(fn (Assert $page) => $page
                ->component('Auth/Login')
                ->where('status', __(Password::PASSWORD_RESET)));
    }

    public function test_password_reset_fails_with_invalid_token(): void
    {
        $user = $this->makeUser();

        $response = $this->post('/reset-password', [
            'token' => 'invalid-token',
            'email' => $user->email,
            'password' => 'NewPassw0rd!',
            'password_confirmation' => 'NewPassw0rd!',
        ]);

        $response->assertSessionHasErrors('email');
    }
}
