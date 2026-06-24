<?php

namespace App\Enums;

enum CommandStatus: string
{
    case PENDING      = 'pending';
    case SENT         = 'sent';
    case ACKNOWLEDGED = 'acknowledged';
    case EXECUTED     = 'executed';
    case FAILED       = 'failed';
    case EXPIRED      = 'expired';

    public function label(): string
    {
        return match($this) {
            self::PENDING      => 'Pending',
            self::SENT         => 'Sent',
            self::ACKNOWLEDGED => 'Acknowledged',
            self::EXECUTED     => 'Executed',
            self::FAILED       => 'Failed',
            self::EXPIRED      => 'Expired',
        };
    }

    public function isTerminal(): bool
    {
        // Terminal = this command's lifecycle is over, no more updates
        return in_array($this, [
            self::EXECUTED,
            self::FAILED,
            self::EXPIRED,
        ]);
    }
}
