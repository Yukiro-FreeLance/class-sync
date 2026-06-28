<?php

namespace App\Enums;

enum AttendancePeriodEventType: string
{
    case Out = 'out';
    case Return = 'return';
    case Note = 'note';

    public function label(): string
    {
        return match ($this) {
            self::Out => 'Left class',
            self::Return => 'Returned to class',
            self::Note => 'Note',
        };
    }
}
