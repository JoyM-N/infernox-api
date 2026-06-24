<?php

namespace App\Enums;

enum RobotStatus: string
{
    case PROVISIONED    = 'provisioned';
    case OFFLINE        = 'offline';
    case ONLINE         = 'online';
    case IDLE           = 'idle';
    case ACTIVE         = 'active';
    case ERROR          = 'error';
    case DECOMMISSIONED = 'decommissioned';

    // Is the robot in a working state?
    public function isOperational(): bool
    {
        return in_array($this, [
            self::ONLINE,
            self::IDLE,
            self::ACTIVE,
        ]);
    }

    // Can the robot receive commands right now?
    public function canReceiveCommands(): bool
    {
        return in_array($this, [
            self::ONLINE,
            self::IDLE,
        ]);
    }

    // Human readable label for the frontend
    public function label(): string
    {
        return match($this) {
            self::PROVISIONED    => 'Provisioned',
            self::OFFLINE        => 'Offline',
            self::ONLINE         => 'Online',
            self::IDLE           => 'Idle',
            self::ACTIVE         => 'Active',
            self::ERROR          => 'Error',
            self::DECOMMISSIONED => 'Decommissioned',
        };
    }

    // Color for the frontend status badge
    public function color(): string
    {
        return match($this) {
            self::PROVISIONED    => 'gray',
            self::OFFLINE        => 'gray',
            self::ONLINE         => 'green',
            self::IDLE           => 'blue',
            self::ACTIVE         => 'amber',
            self::ERROR          => 'red',
            self::DECOMMISSIONED => 'gray',
        };
    }
}
