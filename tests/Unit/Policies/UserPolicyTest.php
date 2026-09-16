<?php

namespace Tests\Unit\Policies;

use App\Models\Role;
use App\Models\User;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserPolicyTest extends TestCase
{
    use RefreshDatabase;

    private UserPolicy $policy;

    private Role $adminRole;

    private Role $managerRole;

    private Role $userRole;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new UserPolicy;

        $this->adminRole = Role::create(['name' => 'admin', 'level' => 100]);
        $this->managerRole = Role::create(['name' => 'manager', 'level' => 50]);
        $this->userRole = Role::create(['name' => 'user', 'level' => 10]);
    }

    private function makeUser(Role $role): User
    {
        return User::factory()->create(['role_id' => $role->id]);
    }

    public function test_admin_can_create_any_role_including_another_admin(): void
    {
        $admin = $this->makeUser($this->adminRole);

        $this->assertTrue($this->policy->create($admin, $this->adminRole->id));
        $this->assertTrue($this->policy->create($admin, $this->managerRole->id));
        $this->assertTrue($this->policy->create($admin, $this->userRole->id));
    }

    public function test_manager_can_create_manager_or_user_but_not_admin(): void
    {
        $manager = $this->makeUser($this->managerRole);

        $this->assertTrue($this->policy->create($manager, $this->userRole->id));
        $this->assertTrue($this->policy->create($manager, $this->managerRole->id));
        $this->assertFalse($this->policy->create($manager, $this->adminRole->id));
    }

    public function test_normal_user_cannot_create_anyone(): void
    {
        $user = $this->makeUser($this->userRole);

        $this->assertFalse($this->policy->create($user, $this->userRole->id));
        $this->assertFalse($this->policy->create($user, $this->managerRole->id));
    }

    public function test_admin_can_update_and_delete_another_admin(): void
    {
        $admin = $this->makeUser($this->adminRole);
        $otherAdmin = $this->makeUser($this->adminRole);

        $this->assertTrue($this->policy->update($admin, $otherAdmin));
        $this->assertTrue($this->policy->delete($admin, $otherAdmin));
    }

    public function test_admin_cannot_update_or_delete_themselves(): void
    {
        $admin = $this->makeUser($this->adminRole);

        $this->assertFalse($this->policy->update($admin, $admin));
        $this->assertFalse($this->policy->delete($admin, $admin));
    }

    public function test_admin_cannot_delete_the_last_remaining_admin(): void
    {
        $admin = $this->makeUser($this->adminRole);
        $otherAdmin = $this->makeUser($this->adminRole);

        $otherAdmin->delete();

        $this->assertFalse($this->policy->delete($admin, $admin));
    }

    public function test_manager_can_manage_normal_users_but_not_peers_or_admins(): void
    {
        $manager = $this->makeUser($this->managerRole);
        $otherManager = $this->makeUser($this->managerRole);
        $admin = $this->makeUser($this->adminRole);
        $user = $this->makeUser($this->userRole);

        $this->assertTrue($this->policy->update($manager, $user));
        $this->assertTrue($this->policy->delete($manager, $user));

        $this->assertFalse($this->policy->update($manager, $otherManager));
        $this->assertFalse($this->policy->update($manager, $admin));
    }

    public function test_normal_user_cannot_manage_another_user(): void
    {
        $user = $this->makeUser($this->userRole);
        $otherUser = $this->makeUser($this->userRole);

        $this->assertFalse($this->policy->update($user, $otherUser));
        $this->assertFalse($this->policy->delete($user, $otherUser));
    }

    public function test_admin_can_restore_a_deleted_user(): void
    {
        $admin = $this->makeUser($this->adminRole);
        $user = $this->makeUser($this->userRole);
        $user->delete();

        $this->assertTrue($this->policy->restore($admin, $user));
    }

    public function test_manager_can_restore_a_lower_level_user_but_not_a_peer(): void
    {
        $manager = $this->makeUser($this->managerRole);
        $otherManager = $this->makeUser($this->managerRole);
        $user = $this->makeUser($this->userRole);
        $user->delete();
        $otherManager->delete();

        $this->assertTrue($this->policy->restore($manager, $user));
        $this->assertFalse($this->policy->restore($manager, $otherManager));
    }

    public function test_actor_cannot_restore_themselves(): void
    {
        $admin = $this->makeUser($this->adminRole);
        $admin->delete();

        $this->assertFalse($this->policy->restore($admin, $admin));
    }

    public function test_admin_can_assign_functional_roles_to_anyone_but_themselves(): void
    {
        $admin = $this->makeUser($this->adminRole);
        $otherAdmin = $this->makeUser($this->adminRole);
        $user = $this->makeUser($this->userRole);

        $this->assertTrue($this->policy->assignFunctionalRoles($admin, $otherAdmin));
        $this->assertTrue($this->policy->assignFunctionalRoles($admin, $user));
        $this->assertFalse($this->policy->assignFunctionalRoles($admin, $admin));
    }

    public function test_manager_can_assign_functional_roles_to_lower_level_user_but_not_peers_or_self(): void
    {
        $manager = $this->makeUser($this->managerRole);
        $otherManager = $this->makeUser($this->managerRole);
        $user = $this->makeUser($this->userRole);

        $this->assertTrue($this->policy->assignFunctionalRoles($manager, $user));
        $this->assertFalse($this->policy->assignFunctionalRoles($manager, $otherManager));
        $this->assertFalse($this->policy->assignFunctionalRoles($manager, $manager));
    }
}
