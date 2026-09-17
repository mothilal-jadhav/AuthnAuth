<?php

namespace Database\Seeders;

use App\Models\LeaveType;
use Illuminate\Database\Seeder;

class LeaveTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            'Annual' => ['default_days_per_year' => 18, 'paid' => true, 'is_unlimited' => false],
            'Sick' => ['default_days_per_year' => 12, 'paid' => true, 'is_unlimited' => false],
            'Casual' => ['default_days_per_year' => 6, 'paid' => true, 'is_unlimited' => false],
            'Unpaid' => ['default_days_per_year' => 0, 'paid' => false, 'is_unlimited' => true],
        ];

        foreach ($types as $name => $attributes) {
            LeaveType::firstOrCreate(['name' => $name], $attributes);
        }
    }
}
