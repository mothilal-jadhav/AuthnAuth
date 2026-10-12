<?php

namespace App\Enums;

/**
 * Hierarchy roles drive who-manages-whom via their ordinal `level` (see
 * UserPolicy); functional roles are additive permission grants only.
 */
enum RoleType: string
{
    case Hierarchy = 'hierarchy';
    case Functional = 'functional';
}
