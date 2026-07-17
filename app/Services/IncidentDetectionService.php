<?php

namespace App\Services;

use App\Enums\FireType;
use App\Enums\IncidentSeverity;
use App\Enums\IncidentStatus;
use App\Models\Incident;
use App\Models\TelemetryReading;

class IncidentDetectionService
{
    // ─────────────────────────────────────────────
    // Main entry point
    // Called every time a telemetry reading arrives
    // Decides whether to open, update, or ignore
    // ─────────────────────────────────────────────
    public function evaluate(TelemetryReading $reading): void
    {
        // If nothing alarming in this reading, do nothing
        if (! $this->isAnomalous($reading)) {
            return;
        }

        // Check if this robot already has an active incident
        $existing = Incident::where('robot_id', $reading->robot_id)
            ->whereIn('status', [
                IncidentStatus::OPEN->value,
                IncidentStatus::INVESTIGATING->value,
                IncidentStatus::SUPPRESSING->value,
            ])
            ->latest('detected_at')
            ->first();

        if ($existing) {
            // Incident already open — just update peak readings
            $existing->updatePeakReadings($reading);
        } else {
            // New incident — open one
            $this->openIncident($reading);
        }
    }

    // ─────────────────────────────────────────────
    // Is this reading showing signs of fire?
    // ─────────────────────────────────────────────
    private function isAnomalous(TelemetryReading $reading): bool
    {
        return $reading->fire_detected
            || $reading->temperature > config('infernox.thresholds.fire_temperature')
            || $reading->smoke_level  > config('infernox.thresholds.smoke_ppm');
    }

    // ─────────────────────────────────────────────
    // Open a new incident
    // ─────────────────────────────────────────────
    private function openIncident(TelemetryReading $reading): Incident
    {
        $fireType = $this->classifyFireType($reading);
    
        $incident = Incident::create([
            'robot_id'                 => $reading->robot_id,
            'status'                   => IncidentStatus::OPEN,
            'severity'                 => $this->calculateSeverity($reading),
            'fire_type'                => $fireType,
            'recommended_extinguisher' => $fireType->recommendedExtinguisher(),
            'lat'                      => $reading->lat,
            'lng'                      => $reading->lng,
            'peak_temperature'         => $reading->temperature,
            'peak_smoke_level'         => $reading->smoke_level,
            'detected_at'              => $reading->recorded_at,
        ]);
    
        // Broadcast to dashboard — operators see alert immediately
        broadcast(new \App\Events\IncidentOpened($incident));
    
        return $incident;
    }

    // ─────────────────────────────────────────────
    // Calculate severity from sensor readings
    // ─────────────────────────────────────────────
    private function calculateSeverity(TelemetryReading $reading): IncidentSeverity
    {
        $temp  = $reading->temperature ?? 0;
        $smoke = $reading->smoke_level ?? 0;

        if ($temp > 500 || $smoke > 2000) {
            return IncidentSeverity::CRITICAL;
        }

        if ($temp > 300 || $smoke > 1000) {
            return IncidentSeverity::HIGH;
        }

        if ($temp > config('infernox.thresholds.fire_temperature')
            || $smoke > config('infernox.thresholds.smoke_ppm')) {
            return IncidentSeverity::MEDIUM;
        }

        return IncidentSeverity::LOW;
    }

    // Classify fire type from sensor data
    // Currently uses raw_payload for extra sensor data
    // Will improve when robot gets gas sensors
    private function classifyFireType(TelemetryReading $reading): FireType
{
    $co_level    = $reading->raw_payload['co_level']    ?? null;
    $gas_type    = $reading->raw_payload['gas_type']    ?? null;
    $smoke_color = $reading->raw_payload['smoke_color'] ?? null;

    // Flammable gas detected → Class B
    if ($gas_type === 'lpg' || $gas_type === 'propane') {
        return FireType::CLASS_B;
    }

    // Cooking oil → Class F
    if ($smoke_color === 'white') {
        return FireType::CLASS_F;
    }

    // Electrical fire indicators:
    // High CO level OR very high heat with no visible flame
    if (($co_level && $co_level > 100)
        || ($reading->temperature > 400 && ! $reading->fire_detected)
    ) {
        return FireType::CLASS_C;
    }

    // Cannot classify — operator updates manually
    return FireType::UNKNOWN;
}
}