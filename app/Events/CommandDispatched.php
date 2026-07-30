<?php

namespace App\Events;

use App\Http\Resources\CommandResource;
use App\Models\RobotCommand;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Broadcast immediately (not queued) so the dashboard sees
 * pending → sent → executed without waiting on the queue worker.
 */
class CommandDispatched implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public RobotCommand $command) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('operations.dashboard'),
            new PrivateChannel("robot.{$this->command->robot_id}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'command.dispatched';
    }

    public function broadcastWith(): array
    {
        return [
            'command' => (new CommandResource(
                $this->command->loadMissing('issuedBy')
            ))->resolve(),
        ];
    }
}
