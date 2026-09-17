<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property string $name
 * @property string $type
 * @property int $level
 */
class Role extends Model
{
    protected $fillable = [
        'name',
        'type',
        'level',
    ];

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class);
    }

    /**
     * Roles that participate in the ordinal management hierarchy
     * (see UserPolicy) — assignable as a user's primary role_id.
     */
    public function scopeHierarchy(Builder $query): Builder
    {
        return $query->where('type', 'hierarchy');
    }

    /**
     * Additive roles that grant extra permissions only — never affect
     * who-manages-whom. Assignable via the role_user pivot.
     */
    public function scopeFunctional(Builder $query): Builder
    {
        return $query->where('type', 'functional');
    }

    /**
     * The `<x-badge>` variant used to render this role — a severity gradient
     * for the hierarchy (admin stands out, manager is mid, user is neutral)
     * and a distinct hue for every functional role, since those are never
     * assigned as a primary role and don't need severity signaling.
     */
    public function badgeVariant(): string
    {
        if ($this->type === 'functional') {
            return 'accent';
        }

        return match ($this->name) {
            'admin' => 'danger',
            'manager' => 'warning',
            default => 'neutral',
        };
    }
}
