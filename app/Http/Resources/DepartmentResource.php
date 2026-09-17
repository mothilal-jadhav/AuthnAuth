<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property int $id
 * @property string $name
 * @property User|null $head
 */
class DepartmentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'head' => $this->whenLoaded('head', fn () => $this->head ? [
                'id' => $this->head->id,
                'name' => $this->head->name,
            ] : null),
            'users_count' => $this->whenCounted('users'),
        ];
    }
}
