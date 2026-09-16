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

        // Functional roles: additive permission grants only. Never used in
        // hierarchy comparisons (level is meaningless for these), assigned
        // via the role_user pivot (see User::functionalRoles()).
        Role::firstOrCreate(['name' => 'HR Officer'], ['type' => 'functional', 'level' => 0]);
        Role::firstOrCreate(['name' => 'Payroll Officer'], ['type' => 'functional', 'level' => 0]);
        Role::firstOrCreate(['name' => 'Recruiter'], ['type' => 'functional', 'level' => 0]);
        Role::firstOrCreate(['name' => 'Attendance Admin'], ['type' => 'functional', 'level' => 0]);
    }
}
