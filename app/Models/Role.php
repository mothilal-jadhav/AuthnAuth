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
}
