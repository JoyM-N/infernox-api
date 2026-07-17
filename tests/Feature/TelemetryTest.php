<?php

namespace Tests\Feature;

use App\Models\Robot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use App\Jobs\ProcessTelemetryJob;
use Tests\TestCase;

class TelemetryTest extends TestCase
{
    use RefreshDatabase;

    private Robot $robot;
    private string $robotToken;

    protected function setUp(): void
    {
        parent::setUp();

        $this->robot = Robot::factory()->create();
        $this->robotToken = $this->robot->createToken(
            'test-robot',
            ['telemetry:write', 'commands:read']
        )->plainTextToken;
    }

    public function test_robot_can_submit_telemetry(): void
    {
        Queue::fake();

        $response = $this->withToken($this->robotToken)
            ->postJson('/api/robot/telemetry', [
                'gps'           => ['lat' => -4.0435, 'lng' => 39.6682],
                'battery'       => 87,
                'temperature'   => 28.5,
                'smoke_level'   => 12.0,
                'fire_detected' => false,
                'timestamp'     => now()->toIso8601String(),
            ]);

        $response->assertStatus(202)
                 ->assertJsonPath('status', 'accepted');

        Queue::assertPushed(ProcessTelemetryJob::class);
    }

    public function test_telemetry_requires_fire_detected_field(): void
    {
        Queue::fake();

        $this->withToken($this->robotToken)
             ->postJson('/api/robot/telemetry', [
                 'battery'     => 87,
                 'temperature' => 28.5,
                 'timestamp'   => now()->toIso8601String(),
                 // missing fire_detected
             ])
             ->assertStatus(422)
             ->assertJsonValidationErrors(['fire_detected']);
    }

    public function test_human_token_cannot_submit_telemetry(): void
    {
        Queue::fake();

        $user = \App\Models\User::factory()->create();
        $userToken = $user->createToken('human')->plainTextToken;

        // Human tokens should not be able to submit robot telemetry
        // The route uses auth:sanctum but we check token abilities
        $this->withToken($userToken)
             ->postJson('/api/robot/telemetry', [
                 'fire_detected' => false,
                 'timestamp'     => now()->toIso8601String(),
             ])
             ->assertStatus(403);
    }

    public function test_telemetry_job_saves_reading_to_database(): void
    {
        $payload = [
            'gps'           => ['lat' => -4.0435, 'lng' => 39.6682],
            'battery'       => 87,
            'temperature'   => 28.5,
            'smoke_level'   => 12.0,
            'fire_detected' => false,
            'timestamp'     => now()->toIso8601String(),
        ];

        $job = new ProcessTelemetryJob(
            robotId:    $this->robot->id,
            payload:    $payload,
            receivedAt: now()->toIso8601String(),
        );

        $job->handle(new \App\Services\IncidentDetectionService());

        $this->assertDatabaseHas('telemetry_readings', [
            'robot_id'      => $this->robot->id,
            'fire_detected' => false,
        ]);
    }

    public function test_fire_detection_opens_incident(): void
    {
        $payload = [
            'gps'           => ['lat' => -4.0435, 'lng' => 39.6682],
            'battery'       => 87,
            'temperature'   => 350.0,  // above 200°C threshold
            'smoke_level'   => 800.0,  // above 500ppm threshold
            'fire_detected' => true,
            'timestamp'     => now()->toIso8601String(),
        ];

        $job = new ProcessTelemetryJob(
            robotId:    $this->robot->id,
            payload:    $payload,
            receivedAt: now()->toIso8601String(),
        );

        $job->handle(new \App\Services\IncidentDetectionService());

        $this->assertDatabaseHas('incidents', [
            'robot_id' => $this->robot->id,
            'status'   => 'open',
        ]);
    }
}