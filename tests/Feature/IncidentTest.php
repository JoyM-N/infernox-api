<?php

namespace Tests\Feature;

use App\Enums\IncidentStatus;
use App\Enums\IncidentSeverity;
use App\Models\Incident;
use App\Models\Robot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IncidentTest extends TestCase
{
    use RefreshDatabase;

    private User $operator;
    private Robot $robot;
    private Incident $incident;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RoleSeeder']);

        $this->operator = User::factory()->create();
        $this->operator->assignRole('operator');

        $this->robot = Robot::factory()->create();

        $this->incident = Incident::factory()->create([
            'robot_id'   => $this->robot->id,
            'status'     => IncidentStatus::OPEN,
            'severity'   => IncidentSeverity::HIGH,
            'detected_at' => now(),
        ]);
    }

    public function test_operator_can_list_incidents(): void
    {
        $this->actingAs($this->operator, 'sanctum')
             ->getJson('/api/incidents')
             ->assertStatus(200)
             ->assertJsonStructure([
                 'data' => [['id', 'status', 'severity', 'detected_at']]
             ]);
    }

    public function test_operator_can_view_incident(): void
    {
        $this->actingAs($this->operator, 'sanctum')
             ->getJson("/api/incidents/{$this->incident->id}")
             ->assertStatus(200)
             ->assertJsonPath('data.id', $this->incident->id)
             ->assertJsonPath('data.status.value', 'open');
    }

    public function test_operator_can_update_incident_status(): void
    {
        $this->actingAs($this->operator, 'sanctum')
             ->putJson("/api/incidents/{$this->incident->id}", [
                 'status' => 'investigating',
             ])
             ->assertStatus(200)
             ->assertJsonPath('data.status.value', 'investigating');

        $this->assertDatabaseHas('incidents', [
            'id'     => $this->incident->id,
            'status' => 'investigating',
        ]);
    }

    public function test_resolved_incident_gets_resolved_at_timestamp(): void
    {
        $this->actingAs($this->operator, 'sanctum')
             ->putJson("/api/incidents/{$this->incident->id}", [
                 'status' => 'resolved',
             ])
             ->assertStatus(200);

        $this->assertNotNull(
            $this->incident->fresh()->resolved_at
        );
    }

    public function test_operator_can_add_incident_update(): void
    {
        $this->actingAs($this->operator, 'sanctum')
             ->postJson("/api/incidents/{$this->incident->id}/updates", [
                 'note'         => 'Dispatched robot to sector 4',
                 'action_taken' => 'dispatched',
             ])
             ->assertStatus(201)
             ->assertJsonPath('message', 'Update logged successfully.');

        $this->assertDatabaseHas('incident_updates', [
            'incident_id'  => $this->incident->id,
            'action_taken' => 'dispatched',
        ]);
    }

    public function test_can_filter_incidents_by_status(): void
    {
        Incident::factory()->create([
            'robot_id'    => $this->robot->id,
            'status'      => IncidentStatus::RESOLVED,
            'detected_at' => now(),
            'resolved_at' => now(),
        ]);

        $response = $this->actingAs($this->operator, 'sanctum')
             ->getJson('/api/incidents?status=open')
             ->assertStatus(200);

        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('open', $data[0]['status']['value']);
    }
}