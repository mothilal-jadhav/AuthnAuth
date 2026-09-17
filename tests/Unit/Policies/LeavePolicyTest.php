<?php

namespace Tests\Unit\Policies;

use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Policies\LeavePolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeavePolicyTest extends TestCase
{
    use RefreshDatabase;

    private LeavePolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new LeavePolicy;
    }

    private function userWithPermissions(array $permissions): User
    {
        $role = Role::create(['name' => 'user-'.uniqid(), 'type' => 'hierarchy', 'level' => 10]);

        foreach ($permissions as $name) {
            $permission = Permission::firstOrCreate(['name' => $name]);
            $role->permissions()->attach($permission);
        }

        return User::factory()->create(['role_id' => $role->id]);
    }

    private function leaveRequestFor(User $user, string $status = 'pending'): LeaveRequest
    {
        $leaveType = LeaveType::create(['name' => 'Annual-'.uniqid(), 'default_days_per_year' => 18]);

        return LeaveRequest::create([
            'user_id' => $user->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => now()->addDay()->toDateString(),
            'end_date' => now()->addDay()->toDateString(),
            'total_days' => 1,
            'status' => $status,
        ]);
    }

    public function test_user_with_leave_apply_permission_can_create(): void
    {
        $user = $this->userWithPermissions(['leave.apply']);

        $this->assertTrue($this->policy->create($user));
    }

    public function test_user_without_leave_apply_permission_cannot_create(): void
    {
        $user = $this->userWithPermissions([]);

        $this->assertFalse($this->policy->create($user));
    }

    public function test_owner_can_view_their_own_request(): void
    {
        $owner = $this->userWithPermissions(['leave.apply']);
        $request = $this->leaveRequestFor($owner);

        $this->assertTrue($this->policy->view($owner, $request));
    }

    public function test_approver_can_view_someone_elses_request(): void
    {
        $owner = $this->userWithPermissions(['leave.apply']);
        $approver = $this->userWithPermissions(['leave.approve']);
        $request = $this->leaveRequestFor($owner);

        $this->assertTrue($this->policy->view($approver, $request));
    }

    public function test_unrelated_user_cannot_view_someone_elses_request(): void
    {
        $owner = $this->userWithPermissions(['leave.apply']);
        $other = $this->userWithPermissions(['leave.apply']);
        $request = $this->leaveRequestFor($owner);

        $this->assertFalse($this->policy->view($other, $request));
    }

    public function test_owner_can_cancel_a_pending_request(): void
    {
        $owner = $this->userWithPermissions(['leave.apply']);
        $request = $this->leaveRequestFor($owner, 'pending');

        $this->assertTrue($this->policy->cancel($owner, $request));
    }

    public function test_owner_cannot_cancel_a_decided_request(): void
    {
        $owner = $this->userWithPermissions(['leave.apply']);
        $request = $this->leaveRequestFor($owner, 'approved');

        $this->assertFalse($this->policy->cancel($owner, $request));
    }

    public function test_non_owner_cannot_cancel_a_request(): void
    {
        $owner = $this->userWithPermissions(['leave.apply']);
        $other = $this->userWithPermissions(['leave.apply']);
        $request = $this->leaveRequestFor($owner, 'pending');

        $this->assertFalse($this->policy->cancel($other, $request));
    }

    public function test_approver_can_decide_someone_elses_request(): void
    {
        $owner = $this->userWithPermissions(['leave.apply']);
        $approver = $this->userWithPermissions(['leave.approve']);
        $request = $this->leaveRequestFor($owner);

        $this->assertTrue($this->policy->decide($approver, $request));
    }

    public function test_approver_cannot_decide_their_own_request(): void
    {
        $approver = $this->userWithPermissions(['leave.approve', 'leave.apply']);
        $request = $this->leaveRequestFor($approver);

        $this->assertFalse($this->policy->decide($approver, $request));
    }

    public function test_non_approver_cannot_decide_a_request(): void
    {
        $owner = $this->userWithPermissions(['leave.apply']);
        $other = $this->userWithPermissions([]);
        $request = $this->leaveRequestFor($owner);

        $this->assertFalse($this->policy->decide($other, $request));
    }
}
