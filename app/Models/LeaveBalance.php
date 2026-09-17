<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $user_id
 * @property int $leave_type_id
 * @property int $year
 * @property float $allocated_days
 * @property float $used_days
 * @property float $carried_over_days
 * @property-read User $user
 * @property-read LeaveType $leaveType
 */
#[Fillable(['user_id', 'leave_type_id', 'year', 'allocated_days', 'used_days', 'carried_over_days'])]
class LeaveBalance extends Model
{
    protected function casts(): array
    {
        return [
            'allocated_days' => 'decimal:1',
            'used_days' => 'decimal:1',
            'carried_over_days' => 'decimal:1',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }

    protected function remainingDays(): Attribute
    {
        return Attribute::get(
            fn () => $this->allocated_days + $this->carried_over_days - $this->used_days
        );
    }
}
