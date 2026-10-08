<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'users.view' => 'Users',
            'users.create' => 'Users',
            'users.update' => 'Users',
            'users.delete' => 'Users',
            'roles.view' => 'Roles',
            'permissions.view' => 'Roles',
        ];

        foreach ($permissions as $name => $group) {
            Permission::firstOrCreate(
                ['name' => $name],
                ['group' => $group]
            );
        }
    }
}
