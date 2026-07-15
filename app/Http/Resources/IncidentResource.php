<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IncidentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'       => $this->id,
            'robot_id' => $this->robot_id,

            'status' => [
                'value' => $this->status->value,
                'label' => $this->status->label(),
                'color' => $this->status->color(),
            ],

            'severity' => [
                'value' => $this->severity->value,
                'label' => $this->severity->label(),
                'color' => $this->severity->color(),
            ],

            // Fire type and extinguisher recommendation
            'fire_type' => $this->when(
                $this->fire_type !== null,
                fn() => [
                    'value'       => $this->fire_type->value,
                    'label'       => $this->fire_type->label(),
                    'danger'      => $this->fire_type->dangerLevel(),
                ]
            ),
            'recommended_extinguisher' => $this->recommended_extinguisher,

            'location' => $this->when(
                $this->lat !== null,
                fn() => [
                    'lat' => (float) $this->lat,
                    'lng' => (float) $this->lng,
                ]
            ),

            'peak_temperature' => $this->peak_temperature,
            'peak_smoke_level' => $this->peak_smoke_level,

            'detected_at'  => $this->detected_at?->toIso8601String(),
            'resolved_at'  => $this->resolved_at?->toIso8601String(),
            'duration'     => $this->resolved_at
                                ? $this->detected_at->diffForHumans($this->resolved_at, true)
                                : 'ongoing',

            'is_active'    => $this->isActive(),

            // Load relationships only when requested
            'robot'   => RobotResource::make($this->whenLoaded('robot')),
            'updates' => IncidentUpdateResource::collection($this->whenLoaded('updates')),
            'commands'=> CommandResource::collection($this->whenLoaded('commands')),
        ];
    }
}