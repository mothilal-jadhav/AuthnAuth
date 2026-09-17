<?php

namespace App\Http\Resources;

use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property bool $must_change_password
 * @property Department|null $department
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class UserResource extends JsonResource
{
    /**
     * Deliberate allow-list — never a raw model passthrough. In particular:
     * never `password`, `remember_token`, or `deleted_at`.
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
            'department' => $this->whenLoaded('department', fn () => $this->department instanceof Department ? [
                'id' => $this->department->id,
                'name' => $this->department->name,
            ] : null),
            'functional_roles' => RoleResource::collection($this->whenLoaded('functionalRoles')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
