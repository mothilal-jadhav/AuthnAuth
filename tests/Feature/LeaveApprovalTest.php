<?php

namespace Tests\Feature;

use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Notifications\LeaveDecisionNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class LeaveApprovalTest extends TestCase
{
    use RefreshDatabase;

    private function actorWithPermissions(array $permissions): User
    {
        $role = Role::create(['name' => 'manager-'.uniqid(), 'type' => 'hierarchy', 'level' => 50]);

        foreach ($permissions as $name) {
            $permission = Permission::firstOrCreate(['name' => $name]);
            $role->permissions()->attach($permission);
        }

        return User::factory()->create(['role_id' => $role->id]);
    }

    private function annualLeaveType(): LeaveType
    {
        return LeaveType::create([
            'name' => 'Annual',
            'default_days_per_year' => 18,
            'paid' => true,
            'is_unlimited' => false,
            'is_active' => true,
        ]);
    }

    private function pendingRequestFor(User $user, LeaveType $leaveType, float $days = 3): LeaveRequest
    {
        $monday = Carbon::now()->next(Carbon::MONDAY);

        return LeaveRequest::create([
            'user_id' => $user->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => $monday->toDateString(),
            'end_date' => $monday->copy()->addDays($days - 1)->toDateString(),
            'total_days' => $days,
            'status' => 'pending',
        ]);
    }

    public function test_guest_cannot_access_approvals_page(): void
    {
        $response = $this->get('/leave/approvals');

        $response->assertRedirect('/login');
    }

    public function test_user_with_leave_approve_permission_can_view_approvals_page(): void
    {
        $actor = $this->actorWithPermissions(['leave.approve']);

        $response = $this->actingAs($actor)->get('/leave/approvals');

        $response->assertStatus(200);
    }

    public function test_user_without_leave_approve_permission_cannot_view_approvals_page(): void
    {
        $actor = $this->actorWithPermissions([]);

        $response = $this->actingAs($actor)->get('/leave/approvals');

        $response->assertStatus(403);
    }

    public function test_approver_can_approve_a_pending_request(): void
    {
        Notification::fake();

        $approver = $this->actorWithPermissions(['leave.approve']);
        $requester = $this->actorWithPermissions(['leave.apply']);
        $leaveType = $this->annualLeaveType();
        $leaveRequest = $this->pendingRequestFor($requester, $leaveType, 3);

        $response = $this->actingAs($approver)->put(route('leave.approvals.approve', $leaveRequest));

        $response->assertRedirect(route('leave.approvals.index'));

        $this->assertDatabaseHas('leave_requests', ['id' => $leaveRequest->id, 'status' => 'approved']);
        $this->assertDatabaseHas('leave_balances', [
            'user_id' => $requester->id,
            'leave_type_id' => $leaveType->id,
            'used_days' => 3,
        ]);

        Notification::assertSentTo($requester, LeaveDecisionNotification::class);
    }

    public function test_approving_an_already_decided_request_does_not_double_increment_balance(): void
    {
        $approver = $this->actorWithPermissions(['leave.approve']);
        $requester = $this->actorWithPermissions(['leave.apply']);
        $leaveType = $this->annualLeaveType();
        $leaveRequest = $this->pendingRequestFor($requester, $leaveType, 3);

        $this->actingAs($approver)->put(route('leave.approvals.approve', $leaveRequest));
        $this->actingAs($approver)->put(route('leave.approvals.approve', $leaveRequest));

        $this->assertDatabaseHas('leave_balances', [
            'user_id' => $requester->id,
            'leave_type_id' => $leaveType->id,
            'used_days' => 3,
        ]);
    }

    public function test_approver_can_reject_a_pending_request_with_a_note(): void
    {
        Notification::fake();

        $approver = $this->actorWithPermissions(['leave.approve']);
        $requester = $this->actorWithPermissions(['leave.apply']);
        $leaveType = $this->annualLeaveType();
        $leaveRequest = $this->pendingRequestFor($requester, $leaveType);

        $response = $this->actingAs($approver)->put(route('leave.approvals.reject', $leaveRequest), [
            'decision_note' => 'Not enough coverage that week.',
        ]);

        $response->assertRedirect(route('leave.approvals.index'));

        $this->assertDatabaseHas('leave_requests', [
            'id' => $leaveRequest->id,
            'status' => 'rejected',
            'decision_note' => 'Not enough coverage that week.',
        ]);

        Notification::assertSentTo($requester, LeaveDecisionNotification::class);
    }

    public function test_rejecting_without_a_decision_note_fails_validation(): void
    {
        $approver = $this->actorWithPermissions(['leave.approve']);
        $requester = $this->actorWithPermissions(['leave.apply']);
        $leaveType = $this->annualLeaveType();
        $leaveRequest = $this->pendingRequestFor($requester, $leaveType);

        $response = $this->actingAs($approver)->put(route('leave.approvals.reject', $leaveRequest), []);

        $response->assertSessionHasErrors('decision_note');
        $this->assertDatabaseHas('leave_requests', ['id' => $leaveRequest->id, 'status' => 'pending']);
    }

    public function test_approver_cannot_approve_their_own_leave_request(): void
    {
        $approver = $this->actorWithPermissions(['leave.approve', 'leave.apply']);
        $leaveType = $this->annualLeaveType();
        $leaveRequest = $this->pendingRequestFor($approver, $leaveType);

        $response = $this->actingAs($approver)->put(route('leave.approvals.approve', $leaveRequest));

        $response->assertStatus(403);
        $this->assertDatabaseHas('leave_requests', ['id' => $leaveRequest->id, 'status' => 'pending']);
    }
}
