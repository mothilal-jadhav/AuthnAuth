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
            'users.restore' => 'Users',
            'roles.assign' => 'Users',
            'roles.view' => 'Users',
            'permissions.view' => 'Users',
            'profile.view' => 'Profile',
            'activity.view' => 'Activity',
            'departments.view' => 'Departments',
            'departments.create' => 'Departments',
            'departments.update' => 'Departments',
            'departments.delete' => 'Departments',
            // Scaffolding for future HRMS modules — not yet enforced by any
            // route/controller. Seeded now so roles can be pre-configured
            // ahead of those features shipping.
            'leave.view' => 'Leave',
            'leave.apply' => 'Leave',
            'leave.approve' => 'Leave',
            'leave.manage' => 'Leave',
            'payroll.view' => 'Payroll',
            'payroll.manage' => 'Payroll',
            'attendance.view' => 'Attendance',
            'attendance.manage' => 'Attendance',
            'recruitment.view' => 'Recruitment',
            'recruitment.manage' => 'Recruitment',
        ];

        foreach ($permissions as $name => $group) {
            Permission::firstOrCreate(
                ['name' => $name],
                ['group' => $group]
            );
        }
    }
}
