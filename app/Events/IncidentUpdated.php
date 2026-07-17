<?php

namespace App\Events;

use App\Http\Resources\IncidentResource;
use App\Models\Incident;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class IncidentUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Incident $incident) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('operations.dashboard'),
            new PrivateChannel("robot.{$this->incident->robot_id}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'incident.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'incident' => (new IncidentResource(
                $this->incident->load(['robot', 'updates.user'])
            ))->resolve(),
        ];
    }
}