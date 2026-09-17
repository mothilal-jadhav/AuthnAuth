<?php

namespace App\Policies;

use App\Models\LeaveRequest;
use App\Models\User;

class LeavePolicy
{
    public function create(User $actor): bool
    {
        return $actor->hasPermission('leave.apply');
    }

    public function view(User $actor, LeaveRequest $target): bool
    {
        return $actor->is($target->user)
            || $actor->hasPermission('leave.approve')
            || $actor->hasPermission('leave.view');
    }

    /**
     * Only the requester may cancel, and only while it's still pending —
     * a decided request is a historical record, not an editable draft.
     */
    public function cancel(User $actor, LeaveRequest $target): bool
    {
        return $actor->is($target->user) && $target->status === 'pending';
    }

    /**
     * An approver can never decide their own request, mirroring
     * UserPolicy's "nobody manages themself" rule.
     */
    public function decide(User $actor, LeaveRequest $target): bool
    {
        if (! $actor->hasPermission('leave.approve')) {
            return false;
        }

        return ! $actor->is($target->user);
    }
}
