<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Cache;

/**
 * @property int $role_id
 * @property bool $must_change_password
 * @property-read Role $role
 */
#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'must_change_password' => 'boolean',
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function hasRole(string $role): bool
    {
        return $this->role->name === $role;
    }

    public function hasPermission(string $permission): bool
    {
        if (! $this->role_id) {
            return false;
        }

        return in_array($permission, $this->cachedRolePermissionNames(), true);
    }

    /**
     * Permission names for this user's role, cached per role so
     * permission-gated routes don't hit role+pivot on every request.
     * Roles/permissions are managed outside the app UI today, so a short
     * TTL (rather than event-based invalidation) is an acceptable tradeoff.
     */
    private function cachedRolePermissionNames(): array
    {
        return Cache::remember(
            "role:{$this->role_id}:permissions",
            now()->addMinutes(30),
            fn () => $this->role->permissions()->pluck('name')->all()
        );
    }
}
