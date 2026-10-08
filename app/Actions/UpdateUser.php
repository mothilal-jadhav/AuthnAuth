<?php

namespace App\Actions;

use App\Models\User;
use App\Notifications\EmailChangedNotification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;

class UpdateUser
{
    /**
     * Update a user's name/email/role, and optionally sync their
     * functional roles. Shared by the web and API update flows.
     *
     * Authorization (UserPolicy::update/assignRole/assignFunctionalRoles) is
     * the caller's responsibility, same as detecting *whether* functional
     * roles were submitted at all — the web form needs a hidden marker
     * field to tell "every checkbox unchecked" apart from "not submitted"
     * over HTML, while a JSON client's intent is unambiguous from key
     * presence alone. This action only needs the already-resolved answer:
     * null means leave functional roles untouched, an array (possibly
     * empty, to clear them) means sync to exactly that set.
     *
     * @param  array{name: string, email: string, role_id: int}  $data
     * @param  int[]|null  $functionalRoleIds
     */
    public function execute(User $user, array $data, ?array $functionalRoleIds = null): User
    {
        $oldEmail = $user->email;

        $user->fill([
            'name' => $data['name'],
            'email' => $data['email'],
        ]);
        $user->role_id = $data['role_id'];
        $user->save();

        if ($user->wasChanged('email')) {
            Notification::route('mail', array_unique([$oldEmail, $user->email]))
                ->notify(new EmailChangedNotification($oldEmail, $user->email, auth()->user()->name));
        }

        if ($functionalRoleIds !== null) {
            $user->functionalRoles()->sync($functionalRoleIds);
        }

        if ($user->wasChanged('role_id') || $functionalRoleIds !== null) {
            Cache::forget("user:{$user->id}:permissions");
        }

        return $user;
    }
}
