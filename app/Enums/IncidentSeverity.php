<?php

namespace App\Enums;

enum IncidentSeverity: string
{
    case LOW      = 'low';
    case MEDIUM   = 'medium';
    case HIGH     = 'high';
    case CRITICAL = 'critical';

    public function label(): string
    {
        return match($this) {
            self::LOW      => 'Low',
            self::MEDIUM   => 'Medium',
            self::HIGH     => 'High',
            self::CRITICAL => 'Critical',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::LOW      => 'blue',
            self::MEDIUM   => 'amber',
            self::HIGH     => 'orange',
            self::CRITICAL => 'red',
        };
    }
}
