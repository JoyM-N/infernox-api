<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RobotResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'serial_number'  => $this->serial_number,
            'name'           => $this->name,
            'model'          => $this->model,

            // Status is an enum — we expose all three properties
            // so the frontend can display label and color without extra logic
            'status' => [
                'value' => $this->status->value,
                'label' => $this->status->label(),
                'color' => $this->status->color(),
            ],

            'battery_level'  => $this->battery_level,
            'is_online'      => $this->isOnline(),
            'is_available'   => $this->isAvailableForCommand(),

            // Only include location if we have coordinates
            'location' => $this->when(
                $this->last_known_lat !== null,
                fn() => [
                    'lat' => (float) $this->last_known_lat,
                    'lng' => (float) $this->last_known_lng,
                ]
            ),

            'last_seen_at'    => $this->last_seen_at?->toIso8601String(),
            'commissioned_at' => $this->commissioned_at?->toIso8601String(),
            'created_at'      => $this->created_at->toIso8601String(),

            // Only present immediately after provisioning
            // Never shown again after that — operator must save it
            'api_token' => $this->when(
                $this->hasAttribute('plain_api_token'),
                fn() => $this->plain_api_token
            ),
        ];
    }
}