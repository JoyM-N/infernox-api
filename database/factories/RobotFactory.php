<?php

namespace Database\Factories;

use App\Enums\RobotStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

class RobotFactory extends Factory
{
    public function definition(): array
    {
        return [
            'serial_number'   => 'INFERNOX-' . $this->faker->unique()->numerify('###'),
            'name'            => 'Unit ' . $this->faker->lexify('?????'),
            'model'           => 'INFERNOX-MK' . $this->faker->numberBetween(1, 3),
            'status'          => RobotStatus::ONLINE,
            'last_known_lat'  => $this->faker->latitude(-5, -3),
            'last_known_lng'  => $this->faker->longitude(39, 41),
            'battery_level'   => $this->faker->numberBetween(20, 100),
            'last_seen_at'    => now()->subSeconds($this->faker->numberBetween(1, 30)),
            'commissioned_at' => now()->subDays($this->faker->numberBetween(1, 30)),
        ];
    }

    public function offline(): static
    {
        return $this->state(['status' => RobotStatus::OFFLINE]);
    }

    public function lowBattery(): static
    {
        return $this->state(['battery_level' => $this->faker->numberBetween(0, 15)]);
    }
}