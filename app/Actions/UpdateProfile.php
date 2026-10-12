<?php

namespace App\Actions;

use App\Models\User;
use App\Notifications\EmailChangedNotification;

class UpdateProfile
{
    /**
     * A user updating their own name/email. Role changes never go through
     * here — those are UpdateUser, behind UserPolicy.
     *
     * @param  array{name: string, email: string}  $data
     */
    public function execute(User $user, array $data): User
    {
        $oldEmail = $user->email;

        $user->fill([
            'name' => $data['name'],
            'email' => $data['email'],
        ]);
        $user->save();

        if ($user->wasChanged('email')) {
            EmailChangedNotification::sendFor($oldEmail, $user, $user);
        }

        return $user;
    }
}
