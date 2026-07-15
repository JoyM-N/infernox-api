<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TelemetryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'robot_id'      => $this->robot_id,
            'location'      => $this->when(
                $this->lat !== null,
                fn() => [
                    'lat' => (float) $this->lat,
                    'lng' => (float) $this->lng,
                ]
            ),
            'battery_level'  => $this->battery_level,
            'temperature'    => $this->temperature,
            'smoke_level'    => $this->smoke_level,
            'fire_detected'  => $this->fire_detected,
            'image_path'     => $this->image_path,
            'recorded_at'    => $this->recorded_at?->toIso8601String(),
            'created_at'     => $this->created_at->toIso8601String(),
        ];
    }
}