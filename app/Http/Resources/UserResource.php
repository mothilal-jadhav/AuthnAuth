<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property bool $must_change_password
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class UserResource extends JsonResource
{
    /**
     * Deliberate allow-list — never a raw model passthrough. In particular:
     * never `password` or `remember_token`.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'must_change_password' => (bool) $this->must_change_password,
            'role' => new RoleResource($this->whenLoaded('role')),
            'functional_roles' => RoleResource::collection($this->whenLoaded('functionalRoles')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'can' => $this->when($request->user() instanceof User, fn () => $this->abilities($request->user())),
        ];
    }

    /**
     * What the requesting actor may do to this user, so a UI can hide
     * actions that would 403. Hints only — the routes still enforce both
     * halves themselves. Both halves are needed: UserPolicy never checks
     * the users.update/users.delete permission (only the route middleware
     * does), so a policy-only flag would offer a manager Delete buttons
     * that always fail.
     *
     * @return array{update: bool, delete: bool}
     */
    private function abilities(User $actor): array
    {
        return [
            'update' => $actor->hasPermission('users.update') && $actor->can('update', $this->resource),
            'delete' => $actor->hasPermission('users.delete') && $actor->can('delete', $this->resource),
        ];
    }
}
