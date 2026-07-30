<?php

namespace Database\Factories;

use App\Enums\CommandStatus;
use App\Models\Robot;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class RobotCommandFactory extends Factory
{
    public function definition(): array
    {
        return [
            'robot_id'     => Robot::factory(),
            'issued_by'    => User::factory(),
            'incident_id'  => null,
            'command_type' => 'stop',
            'payload'      => null,
            'status'       => CommandStatus::PENDING,
            'issued_at'    => now(),
        ];
    }
}
