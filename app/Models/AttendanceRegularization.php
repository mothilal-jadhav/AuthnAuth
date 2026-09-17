<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $user_id
 * @property Carbon $date
 * @property Carbon $requested_clock_in
 * @property Carbon $requested_clock_out
 * @property string $status
 * @property int|null $approver_id
 * @property string|null $decision_note
 * @property-read User $user
 * @property-read User|null $approver
 */
#[Fillable([
    'user_id', 'date', 'requested_clock_in', 'requested_clock_out',
    'reason', 'status', 'approver_id', 'decision_note', 'decided_at',
])]
class AttendanceRegularization extends Model
{
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'requested_clock_in' => 'datetime',
            'requested_clock_out' => 'datetime',
            'decided_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function badgeVariant(): string
    {
        return match ($this->status) {
            'approved' => 'success',
            'rejected' => 'danger',
            default => 'warning',
        };
    }
}
