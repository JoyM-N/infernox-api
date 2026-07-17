<?php

namespace Database\Factories;

use App\Enums\FireType;
use App\Enums\IncidentSeverity;
use App\Enums\IncidentStatus;
use App\Models\Robot;
use Illuminate\Database\Eloquent\Factories\Factory;

class IncidentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'robot_id'                 => Robot::factory(),
            'status'                   => IncidentStatus::OPEN,
            'severity'                 => IncidentSeverity::MEDIUM,
            'fire_type'                => FireType::UNKNOWN,
            'recommended_extinguisher' => FireType::UNKNOWN->recommendedExtinguisher(),
            'lat'                      => $this->faker->latitude(-5, -3),
            'lng'                      => $this->faker->longitude(39, 41),
            'peak_temperature'         => $this->faker->randomFloat(2, 200, 500),
            'peak_smoke_level'         => $this->faker->randomFloat(2, 500, 2000),
            'detected_at'              => now(),
            'resolved_at'              => null,
        ];
    }

    public function resolved(): static
    {
        return $this->state([
            'status'      => IncidentStatus::RESOLVED,
            'resolved_at' => now(),
        ]);
    }

    public function critical(): static
    {
        return $this->state([
            'severity'         => IncidentSeverity::CRITICAL,
            'peak_temperature' => $this->faker->randomFloat(2, 500, 1000),
        ]);
    }
}