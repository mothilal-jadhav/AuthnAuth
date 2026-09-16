<?php

namespace Tests\Unit;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class UserPermissionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_has_permission_includes_permissions_granted_only_via_a_functional_role(): void
    {
        $userRole = Role::create(['name' => 'user', 'type' => 'hierarchy', 'level' => 10]);
        $payrollOfficer = Role::create(['name' => 'Payroll Officer', 'type' => 'functional', 'level' => 0]);
        $payrollView = Permission::create(['name' => 'payroll.view']);

        $payrollOfficer->permissions()->attach($payrollView);

        $withFunctionalRole = User::factory()->create(['role_id' => $userRole->id]);
        $withFunctionalRole->functionalRoles()->attach($payrollOfficer);
        $withFunctionalRole->refresh();

        $withoutFunctionalRole = User::factory()->create(['role_id' => $userRole->id]);

        // Distinct users -> distinct cache keys, so neither assertion here
        // depends on cache-invalidation timing (that's covered separately
        // in test_assigning_a_functional_role_reflects_immediately_...).
        $this->assertTrue($withFunctionalRole->hasPermission('payroll.view'));
        $this->assertFalse($withoutFunctionalRole->hasPermission('payroll.view'));
    }

    public function test_has_role_only_reflects_the_primary_role_not_functional_roles(): void
    {
        $userRole = Role::create(['name' => 'user', 'type' => 'hierarchy', 'level' => 10]);
        $hrOfficer = Role::create(['name' => 'HR Officer', 'type' => 'functional', 'level' => 0]);

        $user = User::factory()->create(['role_id' => $userRole->id]);
        $user->functionalRoles()->attach($hrOfficer);
        $user->refresh();

        $this->assertTrue($user->hasRole('user'));
        $this->assertFalse($user->hasRole('HR Officer'));
        $this->assertTrue($user->hasFunctionalRole('HR Officer'));
    }

    public function test_assigning_a_functional_role_reflects_immediately_when_cache_is_busted(): void
    {
        $userRole = Role::create(['name' => 'user', 'type' => 'hierarchy', 'level' => 10]);
        $recruiter = Role::create(['name' => 'Recruiter', 'type' => 'functional', 'level' => 0]);
        $recruitmentManage = Permission::create(['name' => 'recruitment.manage']);

        $recruiter->permissions()->attach($recruitmentManage);

        $user = User::factory()->create(['role_id' => $userRole->id]);

        // Warm the cache under the old (no functional role) permission set.
        $this->assertFalse($user->hasPermission('recruitment.manage'));

        $user->functionalRoles()->attach($recruiter);
        $user->refresh();

        // Without busting the cache key, this would still read the stale
        // cached set for up to 30 minutes.
        Cache::forget("user:{$user->id}:permissions");

        $this->assertTrue($user->hasPermission('recruitment.manage'));
    }
}
