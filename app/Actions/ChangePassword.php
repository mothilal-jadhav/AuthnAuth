<?php

namespace App\Actions;

use App\Models\User;
use App\Notifications\PasswordChangedNotification;
use Illuminate\Support\Str;

class ChangePassword
{
    /**
     * Set a new password chosen by the account owner. Every path that does
     * that — the forced first-login change, the profile page, and the
     * forgot-password reset — goes through here, so they all clear the
     * temporary-password flag, revoke API tokens (a stolen token must not
     * survive the owner securing their account), and notify the owner.
     * Validation (incl. current_password, where required) is the caller's.
     */
    public function execute(User $user, string $password): void
    {
        $user->password = $password;
        $user->must_change_password = false;
        $user->setRememberToken(Str::random(60));
        $user->save();

        $user->tokens()->delete();

        $user->notify(new PasswordChangedNotification);
    }
}
