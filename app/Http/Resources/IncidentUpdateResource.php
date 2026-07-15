<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IncidentUpdateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'incident_id'  => $this->incident_id,
            'note'         => $this->note,
            'action_taken' => $this->action_taken,
            'created_at'   => $this->created_at->toIso8601String(),

            // Who logged this update
            'logged_by' => $this->when(
                $this->relationLoaded('user'),
                fn() => $this->user ? [
                    'id'   => $this->user->id,
                    'name' => $this->user->name,
                ] : ['name' => 'System']
            ),
        ];
    }
}