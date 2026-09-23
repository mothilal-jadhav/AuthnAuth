<?php

namespace Tests\Unit;

use App\Models\ActivityLog;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_causer_still_resolves_after_the_causer_account_is_soft_deleted(): void
    {
        $role = Role::create(['name' => 'admin', 'type' => 'hierarchy', 'level' => 100]);
        $causer = User::factory()->create(['role_id' => $role->id]);

        $log = ActivityLog::create([
            'causer_id' => $causer->id,
            'action' => 'user.created',
        ]);

        $causer->delete();

        $this->assertNotNull($log->fresh()->causer);
        $this->assertTrue($log->fresh()->causer->is($causer));
    }
}
