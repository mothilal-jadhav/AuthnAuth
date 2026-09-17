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
 * @property Carbon|null $clock_in
 * @property Carbon|null $clock_out
 * @property string $status
 * @property int|null $worked_minutes
 * @property-read User $user
 */
#[Fillable(['user_id', 'date', 'clock_in', 'clock_out', 'status', 'worked_minutes', 'notes'])]
class AttendanceRecord extends Model
{
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'clock_in' => 'datetime',
            'clock_out' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeForMonth(Builder $query, int $year, int $month): Builder
    {
        return $query->whereYear('date', $year)->whereMonth('date', $month);
    }

    /**
     * The `<x-badge>` variant for this record's status.
     */
    public function badgeVariant(): string
    {
        return match ($this->status) {
            'present' => 'success',
            'late' => 'warning',
            'half_day' => 'accent',
            'absent' => 'danger',
            'on_leave' => 'info',
            default => 'neutral',
        };
    }
}
