<?php

namespace App\Policies;

use App\Models\Role;
use App\Models\User;

class UserPolicy
{
    /**
     * Whether $actor may create a user assigned to the given role.
     */
    public function create(User $actor, int $targetRoleId): bool
    {
        return $this->assignRole($actor, $targetRoleId);
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
        if ($actor->role?->name === 'admin') {
            return true;
        }

        $targetLevel = Role::find($roleId)?->level ?? PHP_INT_MAX;

        return ($actor->role?->level ?? 0) > $targetLevel;
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

        if ($target->role?->name === 'admin' && $this->isLastAdmin($target)) {
            return false;
        }

        return true;
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

        if ($actor->role?->name === 'admin') {
            return true;
        }

        return ($actor->role?->level ?? 0) > ($target->role?->level ?? PHP_INT_MAX);
    }

    private function isLastAdmin(User $target): bool
    {
        return User::whereHas('role', fn ($query) => $query->where('name', 'admin'))->count() <= 1;
    }
}
