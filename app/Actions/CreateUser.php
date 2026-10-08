<?php

namespace App\Actions;

use App\Models\User;
use Illuminate\Support\Str;

class CreateUser
{
    /**
     * Create a user with a system-generated temporary password, forcing a
     * password change on first login. Shared by the web and API user-creation
     * flows so the "how an account gets provisioned" rule lives in one place.
     *
     * @param  array{name: string, email: string, role_id: int}  $data
     * @return array{user: User, temporary_password: string}
     */
    public function execute(array $data): array
    {
        $temporaryPassword = Str::password(12);

        $user = new User([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $temporaryPassword,
        ]);
        $user->role_id = $data['role_id'];
        $user->must_change_password = true;
        $user->save();

        return ['user' => $user, 'temporary_password' => $temporaryPassword];
    }
}
