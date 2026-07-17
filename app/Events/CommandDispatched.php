<?php

namespace App\Events;

use App\Http\Resources\CommandResource;
use App\Models\RobotCommand;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CommandDispatched implements ShouldBroadcast
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
                $this->command->load('issuedBy')
            ))->resolve(),
        ];
    }
}