<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommandResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'robot_id'     => $this->robot_id,
            'incident_id'  => $this->incident_id,
            'command_type' => $this->command_type,
            'payload'      => $this->payload,

            'status' => [
                'value' => $this->status->value,
                'label' => $this->status->label(),
            ],

            'issued_at'       => $this->issued_at?->toIso8601String(),
            'acknowledged_at' => $this->acknowledged_at?->toIso8601String(),
            'executed_at'     => $this->executed_at?->toIso8601String(),

            'issued_by' => $this->when(
                $this->relationLoaded('issuedBy'),
                fn() => $this->issuedBy ? [
                    'id'   => $this->issuedBy->id,
                    'name' => $this->issuedBy->name,
                ] : null
            ),
        ];
    }
}