<?php

namespace Tests\Unit\Policies;

use App\Models\AttendanceRegularization;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Policies\AttendanceRegularizationPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceRegularizationPolicyTest extends TestCase
{
    use RefreshDatabase;

    private AttendanceRegularizationPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new AttendanceRegularizationPolicy;
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

    private function regularizationFor(User $user): AttendanceRegularization
    {
        return AttendanceRegularization::create([
            'user_id' => $user->id,
            'date' => now()->subDay()->toDateString(),
            'requested_clock_in' => now()->subDay()->setTime(9, 0),
            'requested_clock_out' => now()->subDay()->setTime(18, 0),
            'reason' => 'Test',
            'status' => 'pending',
        ]);
    }

    public function test_anyone_can_create_a_request(): void
    {
        $user = $this->userWithPermissions([]);

        $this->assertTrue($this->policy->create($user));
    }

    public function test_approver_can_decide_someone_elses_request(): void
    {
        $owner = $this->userWithPermissions([]);
        $approver = $this->userWithPermissions(['attendance.manage']);
        $request = $this->regularizationFor($owner);

        $this->assertTrue($this->policy->decide($approver, $request));
    }

    public function test_approver_cannot_decide_their_own_request(): void
    {
        $approver = $this->userWithPermissions(['attendance.manage']);
        $request = $this->regularizationFor($approver);

        $this->assertFalse($this->policy->decide($approver, $request));
    }

    public function test_non_approver_cannot_decide_a_request(): void
    {
        $owner = $this->userWithPermissions([]);
        $other = $this->userWithPermissions([]);
        $request = $this->regularizationFor($owner);

        $this->assertFalse($this->policy->decide($other, $request));
    }
}
