<?php

namespace App\Events;

use App\Models\Robot;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RobotStatusChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Robot $robot) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('operations.dashboard'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'robot.status_changed';
    }

    public function broadcastWith(): array
    {
        return [
            'robot_id'      => $this->robot->id,
            'serial_number' => $this->robot->serial_number,
            'status'        => [
                'value' => $this->robot->status->value,
                'label' => $this->robot->status->label(),
                'color' => $this->robot->status->color(),
            ],
            'battery_level' => $this->robot->battery_level,
            'is_online'     => $this->robot->isOnline(),
            'last_seen_at'  => $this->robot->last_seen_at?->toIso8601String(),
        ];
    }
}