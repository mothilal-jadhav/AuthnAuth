<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $user_id
 * @property int $leave_type_id
 * @property Carbon $start_date
 * @property Carbon $end_date
 * @property bool $is_half_day
 * @property float $total_days
 * @property string $status
 * @property int|null $approver_id
 * @property string|null $decision_note
 * @property-read User $user
 * @property-read User|null $approver
 * @property-read LeaveType $leaveType
 */
#[Fillable([
    'user_id', 'leave_type_id', 'start_date', 'end_date', 'is_half_day',
    'total_days', 'reason', 'status', 'approver_id', 'decision_note', 'decided_at',
])]
class LeaveRequest extends Model
{
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'is_half_day' => 'boolean',
            'total_days' => 'decimal:1',
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

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected(Builder $query): Builder
    {
        return $query->where('status', 'rejected');
    }

    /**
     * The `<x-badge>` variant for this request's current status.
     */
    public function badgeVariant(): string
    {
        return match ($this->status) {
            'approved' => 'success',
            'rejected' => 'danger',
            'cancelled' => 'neutral',
            default => 'warning',
        };
    }
}
