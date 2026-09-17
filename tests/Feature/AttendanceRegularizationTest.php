<?php

namespace Tests\Feature;

use App\Models\AttendanceRegularization;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceRegularizationTest extends TestCase
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

    private function pendingRegularizationFor(User $user): AttendanceRegularization
    {
        return AttendanceRegularization::create([
            'user_id' => $user->id,
            'date' => now()->subDay()->toDateString(),
            'requested_clock_in' => now()->subDay()->setTime(9, 0),
            'requested_clock_out' => now()->subDay()->setTime(18, 0),
            'reason' => 'Forgot to clock in.',
            'status' => 'pending',
        ]);
    }

    public function test_user_can_request_an_attendance_correction(): void
    {
        $user = $this->actorWithPermissions([]);

        $response = $this->actingAs($user)->post('/attendance/regularize', [
            'date' => now()->subDay()->toDateString(),
            'requested_clock_in' => '09:00',
            'requested_clock_out' => '18:00',
            'reason' => 'Forgot to clock in.',
        ]);

        $response->assertRedirect(route('attendance.index'));
        $this->assertDatabaseHas('attendance_regularizations', [
            'user_id' => $user->id,
            'status' => 'pending',
        ]);
    }

    public function test_guest_cannot_access_regularizations_queue(): void
    {
        $response = $this->get('/attendance/regularizations');

        $response->assertRedirect('/login');
    }

    public function test_user_with_attendance_manage_permission_can_view_regularizations_queue(): void
    {
        $actor = $this->actorWithPermissions(['attendance.manage']);

        $response = $this->actingAs($actor)->get('/attendance/regularizations');

        $response->assertStatus(200);
    }

    public function test_user_without_attendance_manage_permission_cannot_view_regularizations_queue(): void
    {
        $actor = $this->actorWithPermissions([]);

        $response = $this->actingAs($actor)->get('/attendance/regularizations');

        $response->assertStatus(403);
    }

    public function test_approver_can_approve_a_regularization_and_it_creates_an_attendance_record(): void
    {
        $approver = $this->actorWithPermissions(['attendance.manage']);
        $requester = $this->actorWithPermissions([]);
        $regularization = $this->pendingRegularizationFor($requester);

        $response = $this->actingAs($approver)->put(route('attendance.regularizations.approve', $regularization));

        $response->assertRedirect(route('attendance.regularizations.index'));
        $this->assertDatabaseHas('attendance_regularizations', ['id' => $regularization->id, 'status' => 'approved']);
        $this->assertDatabaseHas('attendance_records', [
            'user_id' => $requester->id,
            'worked_minutes' => 540,
        ]);
    }

    public function test_approver_can_reject_a_regularization_with_a_note(): void
    {
        $approver = $this->actorWithPermissions(['attendance.manage']);
        $requester = $this->actorWithPermissions([]);
        $regularization = $this->pendingRegularizationFor($requester);

        $response = $this->actingAs($approver)->put(route('attendance.regularizations.reject', $regularization), [
            'decision_note' => 'No supporting evidence.',
        ]);

        $response->assertRedirect(route('attendance.regularizations.index'));
        $this->assertDatabaseHas('attendance_regularizations', ['id' => $regularization->id, 'status' => 'rejected']);
    }

    public function test_rejecting_without_a_decision_note_fails_validation(): void
    {
        $approver = $this->actorWithPermissions(['attendance.manage']);
        $requester = $this->actorWithPermissions([]);
        $regularization = $this->pendingRegularizationFor($requester);

        $response = $this->actingAs($approver)->put(route('attendance.regularizations.reject', $regularization), []);

        $response->assertSessionHasErrors('decision_note');
    }

    public function test_approver_cannot_decide_their_own_regularization_request(): void
    {
        $approver = $this->actorWithPermissions(['attendance.manage']);
        $regularization = $this->pendingRegularizationFor($approver);

        $response = $this->actingAs($approver)->put(route('attendance.regularizations.approve', $regularization));

        $response->assertStatus(403);
    }
}
