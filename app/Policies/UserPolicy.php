<?php

namespace App\Policies;

use App\Models\Role;
use App\Models\User;

class UserPolicy
{
    /**
     * Whether $actor may create a user assigned to the given role.
     *
     * Unlike assignRole() (used for role changes on existing users), this
     * allows same-level creation for roles above the base "user" tier: a
     * manager may create another manager, but never an admin, and a plain
     * user may never create anyone. Kept separate from assignRole() so
     * this doesn't also open up promoting an existing user to manager
     * during an edit.
     */
    public function create(User $actor, int $targetRoleId): bool
    {
        if ($actor->role->name === 'admin') {
            return true;
        }

        if ($actor->role->name === 'user') {
            return false;
        }

        $targetRole = Role::find($targetRoleId);

        if ($targetRole === null) {
            return false;
        }

        return $actor->role->level >= $targetRole->level;
    }

    /**
     * Whether $actor may assign the given role to a user (on create, or by
     * changing an existing user's role on update).
     *
     * Admin is the one intentional exception to the ordinal rule below: it
     * may assign any role, including another admin. Every other role may
     * only assign roles at a strictly lower level than its own.
     */
    public function assignRole(User $actor, int $roleId): bool
    {
        if ($actor->role->name === 'admin') {
            return true;
        }

        $targetRole = Role::find($roleId);

        if ($targetRole === null) {
            return false;
        }

        return $actor->role->level > $targetRole->level;
    }

    public function update(User $actor, User $target): bool
    {
        return $this->manage($actor, $target);
    }

    public function delete(User $actor, User $target): bool
    {
        if (! $this->manage($actor, $target)) {
            return false;
        }

        if ($target->role->name === 'admin' && $this->isLastAdmin($target)) {
            return false;
        }

        return true;
    }

    public function restore(User $actor, User $target): bool
    {
        return $this->manage($actor, $target);
    }

    /**
     * Whether $actor may assign/remove $target's functional roles
     * (additive permission grants, e.g. Payroll Officer — see
     * User::functionalRoles()). Deliberately reuses manage()'s rule rather
     * than a looser one: this is still a privilege-granting action, so the
     * same self-management block applies — an actor can never grant
     * themselves extra functional access, even if they could otherwise
     * manage lower-level users.
     */
    public function assignFunctionalRoles(User $actor, User $target): bool
    {
        return $this->manage($actor, $target);
    }

    /**
     * Shared rule for update/delete: an actor may never manage themselves.
     * Admin may manage anyone else; every other role may only manage
     * strictly-lower-level roles (mirrors create()'s ordinal rule).
     */
    private function manage(User $actor, User $target): bool
    {
        if ($actor->is($target)) {
            return false;
        }

        if ($actor->role->name === 'admin') {
            return true;
        }

        return $actor->role->level > $target->role->level;
    }

    private function isLastAdmin(User $target): bool
    {
        return User::whereHas('role', fn ($query) => $query->where('name', 'admin'))->count() <= 1;
    }
}
