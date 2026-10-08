<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // Hierarchy roles: higher level manages lower. Leave gaps between
        // tiers so new intermediate roles can be inserted without
        // renumbering these.
        Role::firstOrCreate(['name' => 'admin'], ['type' => 'hierarchy', 'level' => 100]);
        Role::firstOrCreate(['name' => 'manager'], ['type' => 'hierarchy', 'level' => 50]);
        Role::firstOrCreate(['name' => 'user'], ['type' => 'hierarchy', 'level' => 10]);

        // Functional roles (additive permission grants, assigned via the
        // role_user pivot — see User::functionalRoles()) go here as
        // ['type' => 'functional', 'level' => 0]. None are seeded by default.
    }
}
