<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $action
 * @property-read User|null $causer
 * @property-read User|null $subject
 */
#[Fillable(['causer_id', 'subject_id', 'action', 'description', 'properties'])]
class ActivityLog extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'properties' => 'array',
        ];
    }

    public function causer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'causer_id');
    }

    /**
     * The subject is fetched with trashed users included, since a deleted
     * user's own "user.deleted" entry (and any prior history) must still
     * resolve a name in the log after the row is soft-deleted.
     */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(User::class, 'subject_id')->withTrashed();
    }

    /**
     * Record an activity entry. Causer is inferred from the current auth
     * context — null for console/system-initiated actions (e.g.
     * app:create-admin), since there's no request-bound user in that case.
     */
    public static function record(string $action, ?User $subject = null, ?string $description = null, array $properties = []): self
    {
        return static::create([
            'causer_id' => auth()->id(),
            'subject_id' => $subject?->id,
            'action' => $action,
            'description' => $description,
            'properties' => $properties,
        ]);
    }

    /**
     * The `<x-badge>` variant used to render this entry's action, based on
     * the `noun.verb` action-string convention every call site already
     * follows (see `record()` callers) rather than an enumerated list.
     */
    public function badgeVariant(): string
    {
        return match (true) {
            str_ends_with($this->action, 'created') => 'success',
            str_ends_with($this->action, 'deleted') => 'danger',
            str_ends_with($this->action, 'restored') => 'accent',
            str_ends_with($this->action, 'password_changed') => 'warning',
            default => 'info',
        };
    }
}
