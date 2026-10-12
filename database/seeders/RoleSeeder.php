<?php

namespace Database\Seeders;

use App\Enums\RoleType;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // Hierarchy roles: higher level manages lower. Leave gaps between
        // tiers so new intermediate roles can be inserted without
        // renumbering these.
        Role::firstOrCreate(['name' => Role::ADMIN], ['type' => RoleType::Hierarchy, 'level' => 100]);
        Role::firstOrCreate(['name' => Role::MANAGER], ['type' => RoleType::Hierarchy, 'level' => 50]);
        Role::firstOrCreate(['name' => Role::USER], ['type' => RoleType::Hierarchy, 'level' => 10]);

        // Functional roles (additive permission grants, assigned via the
        // role_user pivot — see User::functionalRoles()) go here as
        // ['type' => RoleType::Functional, 'level' => 0]. None are seeded by default.
    }
}
