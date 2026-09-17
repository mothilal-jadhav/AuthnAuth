<?php

namespace App\Actions;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Support\Str;

class CreateUser
{
    /**
     * Create a user with a system-generated temporary password, forcing a
     * password change on first login. Shared by the web and API user-creation
     * flows so the "how an account gets provisioned" rule lives in one place.
     *
     * @param  array{name: string, email: string, role_id: int, department_id?: int|null}  $data
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
        $user->department_id = $data['department_id'] ?? null;
        $user->must_change_password = true;
        $user->save();

        ActivityLog::record(
            'user.created',
            $user,
            auth()->user()->name.' created this account.',
            ['role' => $user->role->name]
        );

        return ['user' => $user, 'temporary_password' => $temporaryPassword];
    }
}
