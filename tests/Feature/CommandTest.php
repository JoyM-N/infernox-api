<?php

namespace Tests\Feature;

use App\Enums\CommandStatus;
use App\Enums\RobotStatus;
use App\Models\Robot;
use App\Models\RobotCommand;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommandTest extends TestCase
{
    use RefreshDatabase;

    private User $operator;
    private Robot $robot;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RoleSeeder']);

        $this->operator = User::factory()->create();
        $this->operator->assignRole('operator');

        $this->robot = Robot::factory()->create([
            'status'        => RobotStatus::ONLINE,
            'battery_level' => 80,
            'last_seen_at'  => now(),
        ]);
    }

    public function test_operator_can_send_drive_command(): void
    {
        $this->actingAs($this->operator, 'sanctum')
            ->postJson("/api/robots/{$this->robot->id}/commands", [
                'command_type' => 'drive',
                'payload'      => ['direction' => 'forward'],
            ])
            ->assertStatus(201)
            ->assertJsonPath('command.command_type', 'drive');

        $this->assertDatabaseHas('robot_commands', [
            'robot_id'     => $this->robot->id,
            'command_type' => 'drive',
            'issued_by'    => $this->operator->id,
        ]);
    }

    public function test_drive_requires_direction(): void
    {
        $this->actingAs($this->operator, 'sanctum')
            ->postJson("/api/robots/{$this->robot->id}/commands", [
                'command_type' => 'drive',
                'payload'      => [],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['payload.direction']);
    }

    public function test_operator_can_send_arm_joint_command(): void
    {
        $this->actingAs($this->operator, 'sanctum')
            ->postJson("/api/robots/{$this->robot->id}/commands", [
                'command_type' => 'arm_joint',
                'payload'      => [
                    'joint'  => 1,
                    'action' => 'up',
                ],
            ])
            ->assertStatus(201)
            ->assertJsonPath('command.command_type', 'arm_joint');
    }

    public function test_arm_joint_requires_joint_and_action(): void
    {
        $this->actingAs($this->operator, 'sanctum')
            ->postJson("/api/robots/{$this->robot->id}/commands", [
                'command_type' => 'arm_joint',
                'payload'      => ['joint' => 1],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['payload.action']);
    }

    public function test_new_drive_command_expires_previous_drive(): void
    {
        $existing = RobotCommand::factory()->create([
            'robot_id'     => $this->robot->id,
            'issued_by'    => $this->operator->id,
            'command_type' => 'drive',
            'payload'      => ['direction' => 'forward'],
            'status'       => CommandStatus::PENDING,
            'issued_at'    => now(),
        ]);

        $this->actingAs($this->operator, 'sanctum')
            ->postJson("/api/robots/{$this->robot->id}/commands", [
                'command_type' => 'drive',
                'payload'      => ['direction' => 'reverse'],
            ])
            ->assertStatus(201);

        $this->assertEquals(CommandStatus::EXPIRED, $existing->fresh()->status);
    }

    public function test_emergency_stop_expires_all_active_commands(): void
    {
        $drive = RobotCommand::factory()->create([
            'robot_id'     => $this->robot->id,
            'issued_by'    => $this->operator->id,
            'command_type' => 'drive',
            'status'       => CommandStatus::PENDING,
            'issued_at'    => now(),
        ]);

        $suppress = RobotCommand::factory()->create([
            'robot_id'     => $this->robot->id,
            'issued_by'    => $this->operator->id,
            'command_type' => 'suppress',
            'status'       => CommandStatus::SENT,
            'issued_at'    => now(),
        ]);

        $this->actingAs($this->operator, 'sanctum')
            ->postJson("/api/robots/{$this->robot->id}/commands", [
                'command_type' => 'emergency_stop',
            ])
            ->assertStatus(201)
            ->assertJsonPath('command.command_type', 'emergency_stop');

        $this->assertEquals(CommandStatus::EXPIRED, $drive->fresh()->status);
        $this->assertEquals(CommandStatus::EXPIRED, $suppress->fresh()->status);
    }
}
