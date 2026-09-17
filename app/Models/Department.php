<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property int|null $head_user_id
 */
class Department extends Model
{
    protected $fillable = [
        'name',
        'head_user_id',
    ];

    public function head(): BelongsTo
    {
        return $this->belongsTo(User::class, 'head_user_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function shift(): HasOne
    {
        return $this->hasOne(DepartmentShift::class);
    }

    /**
     * This department's configured shift, or the organization-wide default
     * (`config('attendance.*')`) when none has been set — so attendance
     * works immediately without forcing every department to configure a
     * shift first. Times are always normalized to "H:i" regardless of
     * source (a DB `time` column vs. a config string), so callers never
     * need to care which one they got.
     *
     * @return array{start_time: string, end_time: string, grace_minutes: int}
     */
    public function effectiveShift(): array
    {
        $shift = $this->shift;

        return [
            'start_time' => Carbon::parse($shift->start_time ?? config('attendance.default_start'))->format('H:i'),
            'end_time' => Carbon::parse($shift->end_time ?? config('attendance.default_end'))->format('H:i'),
            'grace_minutes' => $shift->grace_minutes ?? (int) config('attendance.default_grace_minutes'),
        ];
    }
}
