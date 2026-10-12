<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $admin = Role::where('name', Role::ADMIN)->first();
        $manager = Role::where('name', Role::MANAGER)->first();
        $user = Role::where('name', Role::USER)->first();

        $admin->permissions()->sync(
            Permission::all()->pluck('id')
        );

        $manager->permissions()->sync(
            Permission::whereIn('name', [
                'users.view',
                'users.create',
                'users.update',
                'roles.view',
                'permissions.view',
            ])->pluck('id')
        );

        // Plain users can log in and manage their own profile (no
        // permission needed for that), but nothing else.
        $user->permissions()->sync([]);
    }
}
