<?php

return [
    // Used when a department has no configured DepartmentShift row yet.
    'default_start' => env('ATTENDANCE_DEFAULT_START', '09:00'),
    'default_end' => env('ATTENDANCE_DEFAULT_END', '18:00'),
    'default_grace_minutes' => (int) env('ATTENDANCE_DEFAULT_GRACE_MINUTES', 15),
];
