<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // Higher level manages lower. Leave gaps between tiers so new
        // intermediate roles can be inserted without renumbering these.
        Role::create(['name' => 'admin', 'level' => 100]);
        Role::create(['name' => 'manager', 'level' => 50]);
        Role::create(['name' => 'user', 'level' => 10]);
    }
}