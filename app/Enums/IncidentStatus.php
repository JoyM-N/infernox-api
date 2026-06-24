<?php

namespace App\Enums;

enum IncidentStatus: string
{
    case OPEN           = 'open';
    case INVESTIGATING  = 'investigating';
    case SUPPRESSING    = 'suppressing';
    case RESOLVED       = 'resolved';
    case FALSE_ALARM    = 'false_alarm';

    public function label(): string
    {
        return match($this) {
            self::OPEN          => 'Open',
            self::INVESTIGATING => 'Investigating',
            self::SUPPRESSING   => 'Suppressing',
            self::RESOLVED      => 'Resolved',
            self::FALSE_ALARM   => 'False Alarm',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::OPEN          => 'red',
            self::INVESTIGATING => 'amber',
            self::SUPPRESSING   => 'orange',
            self::RESOLVED      => 'green',
            self::FALSE_ALARM   => 'gray',
        };
    }
}
