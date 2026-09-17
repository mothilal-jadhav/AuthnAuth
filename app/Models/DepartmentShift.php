<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $department_id
 * @property string $start_time
 * @property string $end_time
 * @property int $grace_minutes
 */
#[Fillable(['department_id', 'start_time', 'end_time', 'grace_minutes'])]
class DepartmentShift extends Model
{
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }
}
