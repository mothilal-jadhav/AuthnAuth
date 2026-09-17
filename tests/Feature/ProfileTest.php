<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Role;
use App\Models\User;
use App\Notifications\EmailChangedNotification;
use App\Notifications\PasswordChangedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(array $attributes = []): User
    {
        $role = Role::firstOrCreate(['name' => 'user'], ['level' => 10]);

        return User::factory()->create(array_merge(['role_id' => $role->id], $attributes));
    }

    public function test_guest_cannot_view_profile(): void
    {
        $response = $this->get('/profile');

        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_sees_their_own_name_and_email(): void
    {
        $user = $this->makeUser(['name' => 'Jane Doe', 'email' => 'jane@example.com']);

        $response = $this->actingAs($user)->get('/profile');

        $response->assertOk();
        $response->assertSee('Jane Doe');
        $response->assertSee('jane@example.com');
    }

    public function test_user_can_update_their_own_name_and_email(): void
    {
        $user = $this->makeUser(['name' => 'Old Name', 'email' => 'old@example.com']);

        $response = $this->actingAs($user)->put('/profile', [
            'name' => 'New Name',
            'email' => 'new@example.com',
        ]);

        $response->assertRedirect(route('profile'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'New Name',
            'email' => 'new@example.com',
        ]);
    }

    public function test_updating_email_notifies_old_and_new_address_and_logs_activity(): void
    {
        Notification::fake();

        $user = $this->makeUser(['name' => 'Jane Doe', 'email' => 'old@example.com']);

        $this->actingAs($user)->put('/profile', [
            'name' => 'Jane Doe',
            'email' => 'new@example.com',
        ]);

        Notification::assertSentOnDemand(
            EmailChangedNotification::class,
            function ($notification, $channels, $notifiable) {
                $routes = $notifiable->routes['mail'] ?? [];

                return in_array('old@example.com', $routes, true)
                    && in_array('new@example.com', $routes, true);
            }
        );

        $log = ActivityLog::where('action', 'user.updated')
            ->where('subject_id', $user->id)
            ->firstOrFail();

        $this->assertSame($user->id, $log->causer_id);
        $this->assertSame($user->id, $log->subject_id);
    }

    public function test_updating_name_only_sends_no_email_changed_notification(): void
    {
        Notification::fake();

        $user = $this->makeUser(['name' => 'Old Name', 'email' => 'same@example.com']);

        $this->actingAs($user)->put('/profile', [
            'name' => 'New Name',
            'email' => 'same@example.com',
        ]);

        Notification::assertNothingSent();
    }

    public function test_invalid_name_fails_validation_and_does_not_update(): void
    {
        $user = $this->makeUser(['name' => 'Old Name']);

        $response = $this->actingAs($user)->put('/profile', [
            'name' => 'Invalid123',
            'email' => $user->email,
        ]);

        $response->assertSessionHasErrors('name', null, 'updateProfile');
        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'Old Name']);
    }

    public function test_role_id_in_request_body_is_ignored(): void
    {
        $userRole = Role::firstOrCreate(['name' => 'user'], ['level' => 10]);
        $adminRole = Role::firstOrCreate(['name' => 'admin'], ['level' => 100]);
        $user = $this->makeUser(['role_id' => $userRole->id]);

        $this->actingAs($user)->put('/profile', [
            'name' => $user->name,
            'email' => $user->email,
            'role_id' => $adminRole->id,
        ]);

        $this->assertDatabaseHas('users', ['id' => $user->id, 'role_id' => $userRole->id]);
    }

    public function test_user_can_change_their_own_password_with_correct_current_password(): void
    {
        Notification::fake();

        $user = $this->makeUser(['password' => 'OldPass123!']);

        $response = $this->actingAs($user)->put('/profile/password', [
            'current_password' => 'OldPass123!',
            'password' => 'NewPass456!',
            'password_confirmation' => 'NewPass456!',
        ]);

        $response->assertRedirect(route('profile'));

        $this->post('/logout');
        $this->assertGuest();

        $loginResponse = $this->post('/login', [
            'email' => $user->email,
            'password' => 'NewPass456!',
        ]);

        $loginResponse->assertRedirect();
        $this->assertAuthenticatedAs($user->fresh());

        Notification::assertSentTo($user, PasswordChangedNotification::class);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'user.password_changed',
            'causer_id' => $user->id,
            'subject_id' => $user->id,
        ]);
    }

    public function test_self_service_password_change_revokes_existing_api_tokens(): void
    {
        // Security review finding #1: a stolen API token must not survive
        // a password change.
        $user = $this->makeUser(['password' => 'OldPass123!']);
        $user->createToken('stolen-token');

        $this->assertSame(1, $user->tokens()->count());

        $this->actingAs($user)->put('/profile/password', [
            'current_password' => 'OldPass123!',
            'password' => 'NewPass456!',
            'password_confirmation' => 'NewPass456!',
        ]);

        $this->assertSame(0, $user->tokens()->count());
    }

    public function test_wrong_current_password_is_rejected(): void
    {
        Notification::fake();

        $user = $this->makeUser(['password' => 'OldPass123!']);

        $response = $this->actingAs($user)->put('/profile/password', [
            'current_password' => 'WrongPassword!',
            'password' => 'NewPass456!',
            'password_confirmation' => 'NewPass456!',
        ]);

        $response->assertSessionHasErrors('current_password', null, 'updatePassword');

        $this->post('/logout');

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'OldPass123!',
        ]);

        $this->assertAuthenticatedAs($user);

        Notification::assertNothingSent();
    }

    public function test_mismatched_password_confirmation_is_rejected(): void
    {
        $user = $this->makeUser(['password' => 'OldPass123!']);

        $response = $this->actingAs($user)->put('/profile/password', [
            'current_password' => 'OldPass123!',
            'password' => 'NewPass456!',
            'password_confirmation' => 'DoesNotMatch!',
        ]);

        $response->assertSessionHasErrors('password', null, 'updatePassword');
    }
}
