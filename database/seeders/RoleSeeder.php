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
        Role::firstOrCreate(['name' => 'admin'], ['level' => 100]);
        Role::firstOrCreate(['name' => 'manager'], ['level' => 50]);
        Role::firstOrCreate(['name' => 'user'], ['level' => 10]);
    }
}
