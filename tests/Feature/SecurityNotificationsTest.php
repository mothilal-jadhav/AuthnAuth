<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Notifications\EmailChangedNotification;
use App\Notifications\PasswordChangedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SecurityNotificationsTest extends TestCase
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

    public function test_self_password_change_notifies_the_user(): void
    {
        Notification::fake();

        $role = Role::firstOrCreate(['name' => 'user'], ['level' => 10]);
        $user = User::factory()->create(['role_id' => $role->id]);

        $this->actingAs($user)->post('/password/change', [
            'password' => 'NewPass123!',
            'password_confirmation' => 'NewPass123!',
        ]);

        Notification::assertSentTo($user, PasswordChangedNotification::class);
    }

    public function test_admin_changing_a_users_email_notifies_old_and_new_address(): void
    {
        Notification::fake();

        $admin = $this->actorWithPermissions('admin', ['users.view', 'users.update']);
        $userRole = Role::firstOrCreate(['name' => 'user'], ['level' => 10]);
        $target = User::factory()->create([
            'role_id' => $userRole->id,
            'name' => 'Target Person',
            'email' => 'old@example.com',
        ]);

        $response = $this->actingAs($admin)->put("/users/{$target->id}", [
            'name' => 'Target Person',
            'email' => 'new@example.com',
            'role_id' => $userRole->id,
        ]);

        $response->assertRedirect();

        Notification::assertSentOnDemand(
            EmailChangedNotification::class,
            function ($notification, $channels, $notifiable) {
                $routes = $notifiable->routes['mail'] ?? [];

                return in_array('old@example.com', $routes, true)
                    && in_array('new@example.com', $routes, true);
            }
        );
    }

    public function test_updating_a_user_without_changing_email_sends_no_email_changed_notification(): void
    {
        Notification::fake();

        $admin = $this->actorWithPermissions('admin', ['users.view', 'users.update']);
        $userRole = Role::firstOrCreate(['name' => 'user'], ['level' => 10]);
        $target = User::factory()->create(['role_id' => $userRole->id, 'name' => 'Old Name']);

        $this->actingAs($admin)->put("/users/{$target->id}", [
            'name' => 'New Name',
            'email' => $target->email,
            'role_id' => $userRole->id,
        ]);

        Notification::assertNothingSent();
    }
}
