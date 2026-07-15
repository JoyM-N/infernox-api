<?php

namespace App\Jobs;

use App\Models\Robot;
use App\Models\TelemetryReading;
use App\Services\IncidentDetectionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ProcessTelemetryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // How many times to retry if the job fails
    public int $tries = 3;

    // Wait 5 seconds between retries
    public int $backoff = 5;

    // Job must complete within 30 seconds
    public int $timeout = 30;

    public function __construct(
        public readonly string $robotId,
        public readonly array  $payload,
        public readonly string $receivedAt,
    ) {
        $this->onQueue('telemetry');
    }

    public function handle(IncidentDetectionService $detection): void
    {
        // Step 1 — Find the robot
        $robot = Robot::find($this->robotId);

        if (! $robot) {
            Log::warning("ProcessTelemetryJob: Robot {$this->robotId} not found");
            return;
        }

        // Step 2 — Save the telemetry reading to DB
        $reading = TelemetryReading::create([
            'robot_id'      => $this->robotId,
            'lat'           => $this->payload['gps']['lat']   ?? null,
            'lng'           => $this->payload['gps']['lng']   ?? null,
            'battery_level' => isset($this->payload['battery']) ? (int) $this->payload['battery'] : null,            
            'temperature'   => $this->payload['temperature']  ?? null,
            'smoke_level'   => $this->payload['smoke_level']  ?? null,
            'fire_detected' => $this->payload['fire_detected'] ?? false,
            'image_path'    => $this->payload['image_path']   ?? null,
            'raw_payload'   => $this->payload,
            'recorded_at'   => $this->payload['timestamp'],
        ]);
        // Step 3 — Update robot's last known state
        // Store in Redis for fast dashboard reads
        // Also update MySQL for persistence
        $this->updateRobotSnapshot($robot, $reading);

        // Step 4 — Run incident detection rules
        // Opens an incident if fire is detected
        $detection->evaluate($reading);

        Log::info("Telemetry processed for robot {$this->robotId}", [
            'temp'          => $reading->temperature,
            'smoke'         => $reading->smoke_level,
            'fire_detected' => $reading->fire_detected,
        ]);
    }

    private function updateRobotSnapshot(Robot $robot, TelemetryReading $reading): void
    {
        // Cache latest snapshot in Redis
        // Dashboard reads this instead of hitting MySQL every time
        // TTL: 10 minutes — if robot goes silent, cache expires naturally
        Cache::put(
            "robot:{$robot->id}:snapshot",
            [
                'battery_level' => $reading->battery_level,
                'temperature'   => $reading->temperature,
                'smoke_level'   => $reading->smoke_level,
                'fire_detected' => $reading->fire_detected,
                'lat'           => $reading->lat,
                'lng'           => $reading->lng,
                'recorded_at'   => $reading->recorded_at?->toIso8601String(),
            ],
            600 // seconds
        );

        // Update robot record in MySQL
        $robot->update([
            'last_known_lat'  => $reading->lat,
            'last_known_lng'  => $reading->lng,
            'battery_level'   => $reading->battery_level,
            'last_seen_at'    => now(),
            'status'          => $reading->fire_detected
                                    ? \App\Enums\RobotStatus::ACTIVE
                                    : \App\Enums\RobotStatus::ONLINE,
        ]);
    }

    // Called if all retries fail
    public function failed(\Throwable $exception): void
    {
        Log::error("ProcessTelemetryJob failed for robot {$this->robotId}", [
            'error' => $exception->getMessage(),
        ]);
    }
}