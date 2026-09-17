<?php

namespace App\Actions;

use App\Models\ActivityLog;
use App\Models\User;
use App\Notifications\EmailChangedNotification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;

class UpdateUser
{
    /**
     * Update a user's name/email/role/department, and optionally sync their
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
     * @param  array{name: string, email: string, role_id: int, department_id?: int|null}  $data
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
        $user->department_id = $data['department_id'] ?? null;
        $user->save();

        $changes = $user->getChanges();
        unset($changes['updated_at']);

        ActivityLog::record(
            'user.updated',
            $user,
            auth()->user()->name.' updated this account.',
            $changes
        );

        if (array_key_exists('email', $changes)) {
            Notification::route('mail', array_unique([$oldEmail, $user->email]))
                ->notify(new EmailChangedNotification($oldEmail, $user->email, auth()->user()->name));
        }

        if ($functionalRoleIds !== null) {
            $user->functionalRoles()->sync($functionalRoleIds);

            ActivityLog::record(
                'user.functional_roles_updated',
                $user,
                auth()->user()->name.' updated this account\'s functional roles.',
                ['functional_role_ids' => $functionalRoleIds]
            );
        }

        if (array_key_exists('role_id', $changes) || $functionalRoleIds !== null) {
            Cache::forget("user:{$user->id}:permissions");
        }

        return $user;
    }
}
