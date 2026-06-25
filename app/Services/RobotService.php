<?php

namespace App\Services;

use App\Enums\RobotStatus;
use App\Models\Robot;
use Illuminate\Support\Facades\DB;

class RobotService
{
    // Creates the robot record AND its API token
    // Returns the robot with the plain token attached

    public function provision(array $data): Robot
    {
        return DB::transaction(function () use ($data) {
            // Create the robot record
            $robot = Robot::create([
                'serial_number'   => $data['serial_number'],
                'name'            => $data['name'],
                'model'           => $data['model'],
                'status'          => RobotStatus::PROVISIONED,
                'commissioned_at' => now(),
            ]);

            // Generate the robot's API token
            // abilities define what this token can do:write>can POST telemetry readings,,read>can GET pending commands
            $plainToken = $robot->createToken(
                name: "robot-{$robot->serial_number}",
                abilities: ['telemetry:write', 'commands:read'],
            )->plainTextToken;

            // Attach plain token as a temporary attribute
            // This is the ONLY time this token is available in plain text
            // After this response, it's hashed in the DB and unrecoverable
            // The operator MUST save it now
            $robot->setAttribute('plain_api_token', $plainToken);

            return $robot;
        });
    }

    public function update(Robot $robot, array $data): Robot
    {
        $robot->update($data);
        return $robot->fresh();
    }

    // Soft deletes it and revokes all its tokens
    public function decommission(Robot $robot): void
    {
        if ($robot->activeIncident()->exists()) {
            abort(422, 'Cannot decommission a robot with an active incident.');
        }

        DB::transaction(function () use ($robot) {
            $robot->tokens()->delete();

            $robot->update([
                'status' => RobotStatus::DECOMMISSIONED,
            ]);

            // Soft delete — data is preserved for audit
            $robot->delete();
        });
    }

    // Rotate robot token, Called when a token is suspected compromised
    public function rotateToken(Robot $robot): string
    {
        return DB::transaction(function () use ($robot) {
            $robot->tokens()->delete();
            return $robot->createToken(
                name: "robot-{$robot->serial_number}-rotated-" . now()->timestamp,
                abilities: ['telemetry:write', 'commands:read'],
            )->plainTextToken;
        });
    }
}