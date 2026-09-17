<?php

namespace App\Policies;

use App\Models\AttendanceRegularization;
use App\Models\User;

class AttendanceRegularizationPolicy
{
    /**
     * Any authenticated user may request a correction to their own record
     * — ownership itself is enforced at the controller/action level, same
     * as Leave's self-service actions.
     */
    public function create(User $actor): bool
    {
        return true;
    }

    /**
     * An approver can never decide their own request, mirroring
     * LeavePolicy's "nobody manages themself" rule.
     */
    public function decide(User $actor, AttendanceRegularization $target): bool
    {
        if (! $actor->hasPermission('attendance.manage')) {
            return false;
        }

        return ! $actor->is($target->user);
    }
}
