<?php

namespace App\Services;

use App\Enums\CommandStatus;
use App\Events\CommandDispatched;
use App\Models\RobotCommand;

class CommandLifecycleService
{
    /**
     * Expire commands that were never picked up / finished in time.
     * Default: 2 minutes for pending/sent/acknowledged.
     */
    public function expireStale(int $olderThanSeconds = 120): int
    {
        $commands = RobotCommand::query()
            ->whereIn('status', [
                CommandStatus::PENDING,
                CommandStatus::SENT,
                CommandStatus::ACKNOWLEDGED,
            ])
            ->where('issued_at', '<', now()->subSeconds($olderThanSeconds))
            ->with('issuedBy')
            ->get();

        foreach ($commands as $command) {
            $command->update(['status' => CommandStatus::EXPIRED]);
            broadcast(new CommandDispatched($command->fresh()->load('issuedBy')));
        }

        return $commands->count();
    }
}
