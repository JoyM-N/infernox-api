<?php

namespace App\Events;

use App\Http\Resources\TelemetryResource;
use App\Models\TelemetryReading;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TelemetryReceived implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public TelemetryReading $reading) {}

    // Which channels receive this event
    public function broadcastOn(): array
    {
        return [
            // All operators monitoring the dashboard
            new PrivateChannel('operations.dashboard'),

            // Anyone monitoring this specific robot
            new PrivateChannel("robot.{$this->reading->robot_id}"),
        ];
    }

    // Event name the frontend listens for
    public function broadcastAs(): string
    {
        return 'telemetry.received';
    }

    // Data sent to the frontend
    // Uses our existing TelemetryResource so the shape is consistent
    public function broadcastWith(): array
    {
        return [
            'reading'  => (new TelemetryResource($this->reading))->resolve(),
            'robot_id' => $this->reading->robot_id,
        ];
    }
}