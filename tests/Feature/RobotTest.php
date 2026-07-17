<?php

namespace Tests\Feature;

use App\Enums\RobotStatus;
use App\Models\Robot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RobotTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $operator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RoleSeeder']);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('super_admin');

        $this->operator = User::factory()->create();
        $this->operator->assignRole('operator');
    }

    public function test_super_admin_can_provision_robot(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/robots', [
                'serial_number' => 'INFERNOX-TEST-001',
                'name'          => 'Test Unit',
                'model'         => 'INFERNOX-MK1',
            ]);

        $response->assertStatus(201)
                 ->assertJsonPath('data.serial_number', 'INFERNOX-TEST-001')
                 ->assertJsonPath('data.status.value', 'provisioned')
                 ->assertJsonStructure(['data' => ['api_token']]);

        $this->assertDatabaseHas('robots', [
            'serial_number' => 'INFERNOX-TEST-001',
        ]);
    }

    public function test_operator_cannot_provision_robot(): void
    {
        $this->actingAs($this->operator, 'sanctum')
             ->postJson('/api/robots', [
                 'serial_number' => 'INFERNOX-002',
                 'name'          => 'Test',
                 'model'         => 'MK1',
             ])
             ->assertStatus(403);
    }

    public function test_duplicate_serial_number_is_rejected(): void
    {
        Robot::factory()->create(['serial_number' => 'INFERNOX-001']);

        $this->actingAs($this->admin, 'sanctum')
             ->postJson('/api/robots', [
                 'serial_number' => 'INFERNOX-001',
                 'name'          => 'Duplicate',
                 'model'         => 'MK1',
             ])
             ->assertStatus(422)
             ->assertJsonValidationErrors(['serial_number']);
    }

    public function test_operator_can_list_robots(): void
    {
        Robot::factory()->count(3)->create();

        $this->actingAs($this->operator, 'sanctum')
             ->getJson('/api/robots')
             ->assertStatus(200)
             ->assertJsonStructure([
                 'data' => [['id', 'serial_number', 'name', 'status']]
             ]);
    }

    public function test_operator_can_view_single_robot(): void
    {
        $robot = Robot::factory()->create();

        $this->actingAs($this->operator, 'sanctum')
             ->getJson("/api/robots/{$robot->id}")
             ->assertStatus(200)
             ->assertJsonPath('data.id', $robot->id);
    }

    public function test_serial_number_must_be_uppercase(): void
    {
        $this->actingAs($this->admin, 'sanctum')
             ->postJson('/api/robots', [
                 'serial_number' => 'infernox-001', // lowercase — invalid
                 'name'          => 'Test',
                 'model'         => 'MK1',
             ])
             ->assertStatus(422)
             ->assertJsonValidationErrors(['serial_number']);
    }
}