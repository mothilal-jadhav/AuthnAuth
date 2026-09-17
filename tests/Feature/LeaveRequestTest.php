<?php

namespace Tests\Feature;

use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class LeaveRequestTest extends TestCase
{
    use RefreshDatabase;

    private function actorWithPermissions(array $permissions): User
    {
        $role = Role::create(['name' => 'user-'.uniqid(), 'type' => 'hierarchy', 'level' => 10]);

        foreach ($permissions as $name) {
            $permission = Permission::firstOrCreate(['name' => $name]);
            $role->permissions()->attach($permission);
        }

        return User::factory()->create(['role_id' => $role->id]);
    }

    private function annualLeaveType(int $defaultDays = 18): LeaveType
    {
        return LeaveType::create([
            'name' => 'Annual',
            'default_days_per_year' => $defaultDays,
            'paid' => true,
            'is_unlimited' => false,
            'is_active' => true,
        ]);
    }

    private function nextMonday(): Carbon
    {
        return Carbon::now()->next(Carbon::MONDAY);
    }

    public function test_guest_cannot_access_leave_page(): void
    {
        $response = $this->get('/leave');

        $response->assertRedirect('/login');
    }

    public function test_user_with_leave_apply_permission_can_view_my_leave_page(): void
    {
        $actor = $this->actorWithPermissions(['leave.apply']);

        $response = $this->actingAs($actor)->get('/leave');

        $response->assertStatus(200);
    }

    public function test_user_without_leave_apply_permission_cannot_view_my_leave_page(): void
    {
        $actor = $this->actorWithPermissions([]);

        $response = $this->actingAs($actor)->get('/leave');

        $response->assertStatus(403);
    }

    public function test_user_can_apply_for_leave(): void
    {
        $actor = $this->actorWithPermissions(['leave.apply']);
        $leaveType = $this->annualLeaveType();

        $monday = $this->nextMonday();

        $response = $this->actingAs($actor)->post('/leave', [
            'leave_type_id' => $leaveType->id,
            'start_date' => $monday->toDateString(),
            'end_date' => $monday->copy()->addDays(2)->toDateString(),
            'reason' => 'Family trip',
        ]);

        $response->assertRedirect(route('leave.index'));

        $this->assertDatabaseHas('leave_requests', [
            'user_id' => $actor->id,
            'leave_type_id' => $leaveType->id,
            'status' => 'pending',
            'total_days' => 3,
        ]);
    }

    public function test_applying_for_leave_fails_when_end_date_is_before_start_date(): void
    {
        $actor = $this->actorWithPermissions(['leave.apply']);
        $leaveType = $this->annualLeaveType();

        $monday = $this->nextMonday();

        $response = $this->actingAs($actor)->post('/leave', [
            'leave_type_id' => $leaveType->id,
            'start_date' => $monday->toDateString(),
            'end_date' => $monday->copy()->subDay()->toDateString(),
        ]);

        $response->assertSessionHasErrors('end_date');
        $this->assertDatabaseCount('leave_requests', 0);
    }

    public function test_applying_for_overlapping_leave_is_rejected(): void
    {
        $actor = $this->actorWithPermissions(['leave.apply']);
        $leaveType = $this->annualLeaveType();

        $monday = $this->nextMonday();

        LeaveRequest::create([
            'user_id' => $actor->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => $monday->toDateString(),
            'end_date' => $monday->copy()->addDays(2)->toDateString(),
            'total_days' => 3,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($actor)->post('/leave', [
            'leave_type_id' => $leaveType->id,
            'start_date' => $monday->copy()->addDay()->toDateString(),
            'end_date' => $monday->copy()->addDays(3)->toDateString(),
        ]);

        $response->assertSessionHasErrors('start_date');
        $this->assertDatabaseCount('leave_requests', 1);
    }

    public function test_applying_for_leave_exceeding_balance_is_rejected(): void
    {
        $actor = $this->actorWithPermissions(['leave.apply']);
        $leaveType = $this->annualLeaveType(defaultDays: 2);

        $monday = $this->nextMonday();

        $response = $this->actingAs($actor)->post('/leave', [
            'leave_type_id' => $leaveType->id,
            'start_date' => $monday->toDateString(),
            'end_date' => $monday->copy()->addDays(4)->toDateString(),
        ]);

        $response->assertSessionHasErrors('leave_type_id');
        $this->assertDatabaseCount('leave_requests', 0);
    }

    public function test_user_can_view_their_own_leave_request(): void
    {
        $actor = $this->actorWithPermissions(['leave.apply']);
        $leaveType = $this->annualLeaveType();

        $leaveRequest = LeaveRequest::create([
            'user_id' => $actor->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => $this->nextMonday()->toDateString(),
            'end_date' => $this->nextMonday()->toDateString(),
            'total_days' => 1,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($actor)->get(route('leave.show', $leaveRequest));

        $response->assertStatus(200);
    }

    public function test_user_cannot_view_another_users_leave_request_without_permission(): void
    {
        $actor = $this->actorWithPermissions(['leave.apply']);
        $owner = $this->actorWithPermissions(['leave.apply']);
        $leaveType = $this->annualLeaveType();

        $leaveRequest = LeaveRequest::create([
            'user_id' => $owner->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => $this->nextMonday()->toDateString(),
            'end_date' => $this->nextMonday()->toDateString(),
            'total_days' => 1,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($actor)->get(route('leave.show', $leaveRequest));

        $response->assertStatus(403);
    }

    public function test_user_can_cancel_their_own_pending_request(): void
    {
        $actor = $this->actorWithPermissions(['leave.apply']);
        $leaveType = $this->annualLeaveType();

        $leaveRequest = LeaveRequest::create([
            'user_id' => $actor->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => $this->nextMonday()->toDateString(),
            'end_date' => $this->nextMonday()->toDateString(),
            'total_days' => 1,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($actor)->delete(route('leave.cancel', $leaveRequest));

        $response->assertRedirect(route('leave.index'));
        $this->assertDatabaseHas('leave_requests', ['id' => $leaveRequest->id, 'status' => 'cancelled']);
    }

    public function test_user_cannot_cancel_an_already_approved_request(): void
    {
        $actor = $this->actorWithPermissions(['leave.apply']);
        $leaveType = $this->annualLeaveType();

        $leaveRequest = LeaveRequest::create([
            'user_id' => $actor->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => $this->nextMonday()->toDateString(),
            'end_date' => $this->nextMonday()->toDateString(),
            'total_days' => 1,
            'status' => 'approved',
        ]);

        $response = $this->actingAs($actor)->delete(route('leave.cancel', $leaveRequest));

        $response->assertStatus(403);
        $this->assertDatabaseHas('leave_requests', ['id' => $leaveRequest->id, 'status' => 'approved']);
    }

    public function test_user_cannot_cancel_another_users_request(): void
    {
        $actor = $this->actorWithPermissions(['leave.apply']);
        $owner = $this->actorWithPermissions(['leave.apply']);
        $leaveType = $this->annualLeaveType();

        $leaveRequest = LeaveRequest::create([
            'user_id' => $owner->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => $this->nextMonday()->toDateString(),
            'end_date' => $this->nextMonday()->toDateString(),
            'total_days' => 1,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($actor)->delete(route('leave.cancel', $leaveRequest));

        $response->assertStatus(403);
    }
}
