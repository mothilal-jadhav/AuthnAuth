<?php

namespace App\Actions;

use App\Models\ActivityLog;
use App\Models\User;

class DeleteUser
{
    /**
     * Soft-delete a user. Authorization (UserPolicy::delete — self-deletion
     * block, hierarchy level, last-admin protection) is the caller's
     * responsibility. Shared by the web and API delete flows.
     */
    public function execute(User $user): void
    {
        $user->delete();

        ActivityLog::record(
            'user.deleted',
            $user,
            auth()->user()->name.' deleted this account.'
        );
    }
}
