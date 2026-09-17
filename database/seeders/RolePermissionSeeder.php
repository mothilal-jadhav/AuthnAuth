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
                'users.create',
                'users.update',
                'profile.view',
                'departments.view',
                'roles.assign',
                'roles.view',
                'permissions.view',
            ])->pluck('id')
        );

        $user->permissions()->sync(
            Permission::where('name', 'profile.view')->pluck('id')
        );

        $hrOfficer = Role::where('name', 'HR Officer')->first();
        $payrollOfficer = Role::where('name', 'Payroll Officer')->first();
        $recruiter = Role::where('name', 'Recruiter')->first();
        $attendanceAdmin = Role::where('name', 'Attendance Admin')->first();

        $hrOfficer->permissions()->sync(
            Permission::whereIn('name', [
                'departments.view',
                'leave.view',
                'leave.approve',
            ])->pluck('id')
        );

        $payrollOfficer->permissions()->sync(
            Permission::whereIn('name', [
                'payroll.view',
                'payroll.manage',
            ])->pluck('id')
        );

        $recruiter->permissions()->sync(
            Permission::whereIn('name', [
                'recruitment.view',
                'recruitment.manage',
            ])->pluck('id')
        );

        $attendanceAdmin->permissions()->sync(
            Permission::whereIn('name', [
                'attendance.view',
                'attendance.manage',
            ])->pluck('id')
        );
    }
}
