<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class CreateAdminUserCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_an_admin_user(): void
    {
        Role::create(['name' => 'admin', 'level' => 100]);

        $exitCode = Artisan::call('app:create-admin', ['email' => 'owner@example.com']);

        $this->assertSame(Command::SUCCESS, $exitCode);

        $user = User::where('email', 'owner@example.com')->first();

        $this->assertNotNull($user);
        $this->assertSame('admin', $user->role->name);
        $this->assertTrue($user->must_change_password);
    }

    public function test_it_fails_without_a_seeded_admin_role(): void
    {
        $exitCode = Artisan::call('app:create-admin', ['email' => 'owner@example.com']);

        $this->assertSame(Command::FAILURE, $exitCode);

        $this->assertDatabaseMissing('users', ['email' => 'owner@example.com']);
    }

    public function test_it_refuses_a_duplicate_email(): void
    {
        $role = Role::create(['name' => 'admin', 'level' => 100]);

        User::factory()->create(['email' => 'owner@example.com', 'role_id' => $role->id]);

        $exitCode = Artisan::call('app:create-admin', ['email' => 'owner@example.com']);

        $this->assertSame(Command::FAILURE, $exitCode);
    }

    public function test_it_refuses_to_run_in_production_without_force(): void
    {
        Role::create(['name' => 'admin', 'level' => 100]);

        $this->app['env'] = 'production';

        $exitCode = Artisan::call('app:create-admin', ['email' => 'owner@example.com']);

        $this->assertSame(Command::FAILURE, $exitCode);

        $this->assertDatabaseMissing('users', ['email' => 'owner@example.com']);
    }
}
