<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\AddIncidentUpdateRequest;
use App\Http\Requests\Api\UpdateIncidentRequest;
use App\Http\Resources\IncidentResource;
use App\Http\Resources\IncidentUpdateResource;
use App\Models\Incident;
use App\Models\IncidentUpdate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class IncidentController extends Controller
{
    // ─────────────────────────────────────────────
    // GET /api/incidents
    // List incidents with filters
    // ─────────────────────────────────────────────
    public function index(Request $request): AnonymousResourceCollection
    {
        $incidents = Incident::query()
            ->with(['robot', 'updates.user'])
            ->when(
                $request->status,
                fn($q) => $q->where('status', $request->status)
            )
            ->when(
                $request->severity,
                fn($q) => $q->where('severity', $request->severity)
            )
            ->when(
                $request->robot_id,
                fn($q) => $q->where('robot_id', $request->robot_id)
            )
            ->when(
                $request->boolean('active'),
                fn($q) => $q->active()
            )
            ->orderBy('detected_at', 'desc')
            ->paginate($request->integer('per_page', 20));

        return IncidentResource::collection($incidents);
    }

    // ─────────────────────────────────────────────
    // GET /api/incidents/{incident}
    // Single incident with full details
    // ─────────────────────────────────────────────
    public function show(Incident $incident): IncidentResource
    {
        return new IncidentResource(
            $incident->load(['robot', 'updates.user', 'commands.issuedBy'])
        );
    }

    // ─────────────────────────────────────────────
    // PUT /api/incidents/{incident}
    // Operator updates incident status/severity
    // ─────────────────────────────────────────────
    public function update(
        UpdateIncidentRequest $request,
        Incident $incident
    ): IncidentResource {
        $data = $request->validated();

        // If operator manually sets fire type
        // auto-set the extinguisher recommendation
        if (isset($data['fire_type'])) {
            $fireType = \App\Enums\FireType::from($data['fire_type']);
            $data['recommended_extinguisher'] = $fireType->recommendedExtinguisher();
        }

        // If resolving, record the time
        if (isset($data['status']) &&
            in_array($data['status'], ['resolved', 'false_alarm']) &&
            ! $incident->resolved_at
        ) {
            $data['resolved_at'] = now();
        }

        $incident->update($data);

        return new IncidentResource(
            $incident->fresh()->load(['robot', 'updates.user'])
        );
    }

    // ─────────────────────────────────────────────
    // POST /api/incidents/{incident}/updates
    // Operator logs what action they took
    // ─────────────────────────────────────────────
    public function addUpdate(
        AddIncidentUpdateRequest $request,
        Incident $incident
    ): JsonResponse {
        $update = IncidentUpdate::create([
            'incident_id'  => $incident->id,
            'user_id'      => auth()->id(),
            'note'         => $request->note,
            'action_taken' => $request->action_taken,
        ]);

        return response()->json([
            'message' => 'Update logged successfully.',
            'update'  => new IncidentUpdateResource(
                $update->load('user')
            ),
        ], 201);
    }
}