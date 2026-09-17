<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property int $default_days_per_year
 * @property bool $paid
 * @property bool $is_unlimited
 * @property bool $is_active
 */
#[Fillable(['name', 'default_days_per_year', 'paid', 'is_unlimited', 'is_active'])]
class LeaveType extends Model
{
    protected function casts(): array
    {
        return [
            'paid' => 'boolean',
            'is_unlimited' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function balances(): HasMany
    {
        return $this->hasMany(LeaveBalance::class);
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }
}
