<?php

namespace App\Models;

use App\Enums\RoleType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property string $name
 * @property RoleType $type
 * @property int $level
 */
#[Fillable(['name', 'type', 'level'])]
class Role extends Model
{
    public const ADMIN = 'admin';

    public const MANAGER = 'manager';

    public const USER = 'user';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => RoleType::class,
        ];
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class);
    }

    /**
     * The one role exempt from UserPolicy's ordinal rule — may manage and
     * assign anyone, including other admins.
     */
    public function isAdmin(): bool
    {
        return $this->name === self::ADMIN;
    }

    /**
     * Roles that participate in the ordinal management hierarchy
     * (see UserPolicy) — assignable as a user's primary role_id.
     */
    public function scopeHierarchy(Builder $query): Builder
    {
        return $query->where('type', RoleType::Hierarchy->value);
    }

    /**
     * Additive roles that grant extra permissions only — never affect
     * who-manages-whom. Assignable via the role_user pivot.
     */
    public function scopeFunctional(Builder $query): Builder
    {
        return $query->where('type', RoleType::Functional->value);
    }
}
