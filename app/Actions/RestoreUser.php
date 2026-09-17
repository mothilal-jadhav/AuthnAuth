<?php

namespace App\Actions;

use App\Models\ActivityLog;
use App\Models\User;

class RestoreUser
{
    /**
     * Restore a soft-deleted user. Authorization (UserPolicy::restore) is
     * the caller's responsibility. Shared by the web and API restore flows.
     */
    public function execute(User $user): void
    {
        $user->restore();

        ActivityLog::record(
            'user.restored',
            $user,
            auth()->user()->name.' restored this account.'
        );
    }
}
