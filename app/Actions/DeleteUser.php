<?php

namespace App\Actions;

use App\Models\User;

class DeleteUser
{
    /**
     * Permanently delete a user and revoke their API tokens (the
     * personal_access_tokens morph has no FK to cascade). Authorization (UserPolicy::delete — self-deletion
     * block, hierarchy level, last-admin protection) is the caller's
     * responsibility. Shared by the web and API delete flows.
     */
    public function execute(User $user): void
    {
        $user->tokens()->delete();
        $user->delete();
    }
}
