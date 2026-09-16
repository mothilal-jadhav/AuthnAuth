<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FunctionalRoleAssignmentTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Every scenario here goes through PUT /users/{user}, which requires
     * the users.update permission at the route-middleware layer before the
     * UserPolicy-driven checks under test even run.
     */
    private function makeHierarchyRole(string $name, int $level): Role
    {
        $role = Role::create(['name' => $name, 'type' => 'hierarchy', 'level' => $level]);
        $role->permissions()->attach(Permission::firstOrCreate(['name' => 'users.update']));

        return $role;
    }

    private function updatePayload(User $user, array $overrides = []): array
    {
        return array_merge([
            'name' => $user->name,
            'email' => $user->email,
            'role_id' => $user->role_id,
        ], $overrides);
    }

    public function test_admin_can_assign_a_functional_role_to_a_user_and_it_takes_effect_immediately(): void
    {
        $adminRole = $this->makeHierarchyRole('admin', 100);
        $userRole = $this->makeHierarchyRole('user', 10);

        $payrollOfficer = Role::create(['name' => 'Payroll Officer', 'type' => 'functional', 'level' => 0]);
        $payrollView = Permission::create(['name' => 'payroll.view']);
        $payrollOfficer->permissions()->attach($payrollView);

        $admin = User::factory()->create(['role_id' => $adminRole->id]);
        $target = User::factory()->create(['role_id' => $userRole->id]);

        $this->assertFalse($target->hasPermission('payroll.view'));

        $response = $this->actingAs($admin)->put("/users/{$target->id}", $this->updatePayload($target, [
            'functional_role_ids_submitted' => '1',
            'functional_role_ids' => [$payrollOfficer->id],
        ]));

        $response->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('role_user', [
            'user_id' => $target->id,
            'role_id' => $payrollOfficer->id,
        ]);

        // No manual Cache::forget here — the controller must have busted it.
        $this->assertTrue($target->fresh()->hasPermission('payroll.view'));

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'user.functional_roles_updated',
            'subject_id' => $target->id,
            'causer_id' => $admin->id,
        ]);
    }

    public function test_unchecking_all_functional_roles_removes_them(): void
    {
        $adminRole = $this->makeHierarchyRole('admin', 100);
        $userRole = $this->makeHierarchyRole('user', 10);
        $recruiter = Role::create(['name' => 'Recruiter', 'type' => 'functional', 'level' => 0]);

        $admin = User::factory()->create(['role_id' => $adminRole->id]);
        $target = User::factory()->create(['role_id' => $userRole->id]);
        $target->functionalRoles()->attach($recruiter);

        $response = $this->actingAs($admin)->put("/users/{$target->id}", $this->updatePayload($target, [
            'functional_role_ids_submitted' => '1',
            // No functional_role_ids key at all — mirrors a browser
            // submitting the form with every checkbox unchecked.
        ]));

        $response->assertRedirect(route('users.index'));

        $this->assertDatabaseMissing('role_user', [
            'user_id' => $target->id,
            'role_id' => $recruiter->id,
        ]);
    }

    public function test_manager_cannot_assign_a_functional_role_to_themselves(): void
    {
        $managerRole = $this->makeHierarchyRole('manager', 50);
        $hrOfficer = Role::create(['name' => 'HR Officer', 'type' => 'functional', 'level' => 0]);

        $manager = User::factory()->create(['role_id' => $managerRole->id]);

        $response = $this->actingAs($manager)->put("/users/{$manager->id}", $this->updatePayload($manager, [
            'functional_role_ids_submitted' => '1',
            'functional_role_ids' => [$hrOfficer->id],
        ]));

        $response->assertStatus(403);

        $this->assertDatabaseMissing('role_user', [
            'user_id' => $manager->id,
            'role_id' => $hrOfficer->id,
        ]);
    }

    public function test_manager_cannot_assign_a_functional_role_to_a_peer_manager(): void
    {
        $managerRole = $this->makeHierarchyRole('manager', 50);
        $hrOfficer = Role::create(['name' => 'HR Officer', 'type' => 'functional', 'level' => 0]);

        $manager = User::factory()->create(['role_id' => $managerRole->id]);
        $peer = User::factory()->create(['role_id' => $managerRole->id]);

        $response = $this->actingAs($manager)->put("/users/{$peer->id}", $this->updatePayload($peer, [
            'functional_role_ids_submitted' => '1',
            'functional_role_ids' => [$hrOfficer->id],
        ]));

        $response->assertStatus(403);

        $this->assertDatabaseMissing('role_user', [
            'user_id' => $peer->id,
            'role_id' => $hrOfficer->id,
        ]);
    }

    public function test_updating_a_user_without_the_functional_roles_marker_leaves_functional_roles_untouched(): void
    {
        $adminRole = $this->makeHierarchyRole('admin', 100);
        $userRole = $this->makeHierarchyRole('user', 10);
        $recruiter = Role::create(['name' => 'Recruiter', 'type' => 'functional', 'level' => 0]);

        $admin = User::factory()->create(['role_id' => $adminRole->id]);
        $target = User::factory()->create(['role_id' => $userRole->id]);
        $target->functionalRoles()->attach($recruiter);

        $response = $this->actingAs($admin)->put("/users/{$target->id}", $this->updatePayload($target, [
            'name' => 'Renamed Only',
        ]));

        $response->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('role_user', [
            'user_id' => $target->id,
            'role_id' => $recruiter->id,
        ]);
    }
}
