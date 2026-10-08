<?php

namespace Tests\Unit;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class UserPermissionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_has_permission_includes_permissions_granted_only_via_a_functional_role(): void
    {
        $userRole = Role::create(['name' => 'user', 'type' => 'hierarchy', 'level' => 10]);
        $auditor = Role::create(['name' => 'Auditor', 'type' => 'functional', 'level' => 0]);
        $reportsView = Permission::create(['name' => 'reports.view']);

        $auditor->permissions()->attach($reportsView);

        $withFunctionalRole = User::factory()->create(['role_id' => $userRole->id]);
        $withFunctionalRole->functionalRoles()->attach($auditor);
        $withFunctionalRole->refresh();

        $withoutFunctionalRole = User::factory()->create(['role_id' => $userRole->id]);

        // Distinct users -> distinct cache keys, so neither assertion here
        // depends on cache-invalidation timing (that's covered separately
        // in test_assigning_a_functional_role_reflects_immediately_...).
        $this->assertTrue($withFunctionalRole->hasPermission('reports.view'));
        $this->assertFalse($withoutFunctionalRole->hasPermission('reports.view'));
    }

    public function test_has_role_only_reflects_the_primary_role_not_functional_roles(): void
    {
        $userRole = Role::create(['name' => 'user', 'type' => 'hierarchy', 'level' => 10]);
        $supportAgent = Role::create(['name' => 'Support Agent', 'type' => 'functional', 'level' => 0]);

        $user = User::factory()->create(['role_id' => $userRole->id]);
        $user->functionalRoles()->attach($supportAgent);
        $user->refresh();

        $this->assertTrue($user->hasRole('user'));
        $this->assertFalse($user->hasRole('Support Agent'));
        $this->assertTrue($user->hasFunctionalRole('Support Agent'));
    }

    public function test_checking_permissions_does_not_run_a_query_per_functional_role(): void
    {
        $userRole = Role::create(['name' => 'user', 'type' => 'hierarchy', 'level' => 10]);
        $permission = Permission::create(['name' => 'reports.view']);

        $functionalRoles = collect(range(1, 3))->map(function (int $i) use ($permission) {
            $role = Role::create(['name' => "Functional {$i}", 'type' => 'functional', 'level' => 0]);
            $role->permissions()->attach($permission);

            return $role;
        });

        $user = User::factory()->create(['role_id' => $userRole->id]);
        $user->functionalRoles()->attach($functionalRoles->pluck('id'));
        $user->refresh();

        DB::enableQueryLog();
        $user->hasPermission('reports.view');
        $queryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        // A fixed, small number of queries regardless of how many
        // functional roles are attached (role+its permissions,
        // functionalRoles+their permissions) -- not one query per role.
        $this->assertLessThanOrEqual(4, $queryCount);
    }

    public function test_assigning_a_functional_role_reflects_immediately_when_cache_is_busted(): void
    {
        $userRole = Role::create(['name' => 'user', 'type' => 'hierarchy', 'level' => 10]);
        $reviewer = Role::create(['name' => 'Reviewer', 'type' => 'functional', 'level' => 0]);
        $reportsExport = Permission::create(['name' => 'reports.export']);

        $reviewer->permissions()->attach($reportsExport);

        $user = User::factory()->create(['role_id' => $userRole->id]);

        // Warm the cache under the old (no functional role) permission set.
        $this->assertFalse($user->hasPermission('reports.export'));

        $user->functionalRoles()->attach($reviewer);
        $user->refresh();

        // Without busting the cache key, this would still read the stale
        // cached set for up to 30 minutes.
        Cache::forget("user:{$user->id}:permissions");

        $this->assertTrue($user->hasPermission('reports.export'));
    }
}
