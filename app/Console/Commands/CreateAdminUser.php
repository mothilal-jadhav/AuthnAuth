<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

#[Signature('app:create-admin {email} {--password=} {--force}')]
#[Description('Provision the first admin account for a fresh install')]
class CreateAdminUser extends Command
{
    public function handle(): int
    {
        if ($this->laravel->environment('production') && ! $this->option('force')) {
            $this->error('Refusing to run in production without --force.');

            return self::FAILURE;
        }

        $email = $this->argument('email');

        $validator = Validator::make(['email' => $email], [
            'email' => ['required', 'email', 'unique:users,email'],
        ]);

        if ($validator->fails()) {
            $this->error($validator->errors()->first('email'));

            return self::FAILURE;
        }

        $adminRole = Role::where('name', 'admin')->first();

        if (! $adminRole) {
            $this->error('No "admin" role found. Run the RoleSeeder first.');

            return self::FAILURE;
        }

        $password = $this->option('password') ?: Str::password(16);

        $user = new User([
            'name' => 'Admin',
            'email' => $email,
            'password' => $password,
        ]);
        $user->role_id = $adminRole->id;
        $user->must_change_password = true;
        $user->save();

        $this->info("Admin account created: {$email}");

        if (! $this->option('password')) {
            $this->warn("Temporary password: {$password}");
            $this->line('The user will be required to change it on first login.');
        }

        return self::SUCCESS;
    }
}
