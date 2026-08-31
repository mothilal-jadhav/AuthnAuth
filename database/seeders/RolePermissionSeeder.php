<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $admin = Role::where('name', 'admin')->first();
        $manager = Role::where('name', 'manager')->first();
        $user = Role::where('name', 'user')->first();

        $admin->permissions()->sync(
            Permission::all()->pluck('id')
        );

        $manager->permissions()->sync(
            Permission::whereIn('name', [
                'users.view',
                'users.update',
            ])->pluck('id')
        );

        $user->permissions()->sync(
            Permission::where('name', 'profile.view')->pluck('id')
        );
    }
}
