<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Cache;

/**
 * @property int $role_id
 * @property int|null $department_id
 * @property bool $must_change_password
 * @property-read Role $role
 * @property-read Department|null $department
 * @property-read Collection<int, Role> $functionalRoles
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

    /**
     * The single primary/hierarchy role. Governs management authorization
     * (see UserPolicy) via its ordinal `level` — untouched by functional
     * roles below.
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Additive roles (e.g. Payroll Officer) that grant extra permissions
     * only. Never consulted by hasRole() or UserPolicy's hierarchy checks.
     */
    public function functionalRoles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user');
    }

    public function hasRole(string $role): bool
    {
        return $this->role->name === $role;
    }

    public function hasFunctionalRole(string $role): bool
    {
        return $this->functionalRoles->contains('name', $role);
    }

    public function hasPermission(string $permission): bool
    {
        if (! $this->role_id) {
            return false;
        }

        return in_array($permission, $this->cachedPermissionNames(), true);
    }

    /**
     * Union of the primary role's permissions and every assigned functional
     * role's permissions, cached per user (not per role) since the
     * effective set now depends on which functional roles this specific
     * user holds. Role -> permission mappings are still seeder-only, so a
     * TTL-only tradeoff remains fine for those; but user -> functional role
     * assignment is a real admin action (UserManagementController::update()),
     * so that call site explicitly busts this key rather than waiting out
     * the TTL.
     */
    private function cachedPermissionNames(): array
    {
        return Cache::remember(
            "user:{$this->id}:permissions",
            now()->addMinutes(30),
            function () {
                $names = $this->role->permissions()->pluck('name');

                foreach ($this->functionalRoles as $functionalRole) {
                    $names = $names->merge($functionalRole->permissions()->pluck('name'));
                }

                return $names->unique()->values()->all();
            }
        );
    }
}
