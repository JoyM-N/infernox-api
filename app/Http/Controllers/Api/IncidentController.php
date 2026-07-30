<?php

namespace App\Http\Controllers\Api;

use App\Events\IncidentUpdated;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\AddIncidentUpdateRequest;
use App\Http\Requests\Api\AssignIncidentRequest;
use App\Http\Requests\Api\LockIncidentRequest;
use App\Http\Requests\Api\UpdateIncidentRequest;
use App\Http\Resources\IncidentResource;
use App\Http\Resources\IncidentUpdateResource;
use App\Models\Incident;
use App\Models\IncidentUpdate;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;

class IncidentController extends Controller
{
    // ─────────────────────────────────────────────
    // GET /api/incidents
    // List incidents with filters
    // ─────────────────────────────────────────────
    public function index(Request $request): AnonymousResourceCollection
    {
        $incidents = Incident::query()
            ->with(['robot', 'updates.user', 'assignedOperator', 'lockedBy'])
            ->when(
                $request->status,
                fn ($q) => $q->where('status', $request->status)
            )
            ->when(
                $request->severity,
                fn ($q) => $q->where('severity', $request->severity)
            )
            ->when(
                $request->robot_id,
                fn ($q) => $q->where('robot_id', $request->robot_id)
            )
            ->when(
                $request->boolean('active'),
                fn ($q) => $q->active()
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
            $incident->load([
                'robot',
                'updates.user',
                'commands.issuedBy',
                'assignedOperator',
                'lockedBy',
            ])
        );
    }

    // ─────────────────────────────────────────────
    // PUT /api/incidents/{incident}
    // Operator updates incident status/severity
    // ─────────────────────────────────────────────
    public function update(
        UpdateIncidentRequest $request,
        Incident $incident
    ): IncidentResource|JsonResponse {
        if ($incident->isLocked()) {
            return response()->json([
                'message' => 'This incident is locked and cannot be edited.',
            ], 423);
        }

        $data = $request->validated();

        if (isset($data['fire_type'])) {
            $fireType = \App\Enums\FireType::from($data['fire_type']);
            $data['recommended_extinguisher'] = $fireType->recommendedExtinguisher();
        }

        if (isset($data['status']) &&
            in_array($data['status'], ['resolved', 'false_alarm']) &&
            ! $incident->resolved_at
        ) {
            $data['resolved_at'] = now();
        }

        $incident->update($data);

        broadcast(new IncidentUpdated($incident->fresh()));

        return new IncidentResource(
            $incident->fresh()->load(['robot', 'updates.user', 'assignedOperator', 'lockedBy'])
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
        if ($incident->isLocked()) {
            return response()->json([
                'message' => 'This incident is locked and cannot be edited.',
            ], 423);
        }

        $userId = Auth::id();

        $update = IncidentUpdate::create([
            'incident_id'  => $incident->id,
            'user_id'      => $userId,
            'note'         => $request->note,
            'action_taken' => $request->action_taken,
        ]);

        // First acknowledgment assigns this operator if nobody is assigned yet
        if (
            $request->action_taken === 'acknowledged'
            && $incident->assigned_operator_id === null
        ) {
            $incident->update(['assigned_operator_id' => $userId]);
        }

        return response()->json([
            'message' => 'Update logged successfully.',
            'update'  => new IncidentUpdateResource(
                $update->load('user')
            ),
        ], 201);
    }

    // ─────────────────────────────────────────────
    // POST /api/incidents/{incident}/assign
    // Take charge of an incident (accountability)
    // ─────────────────────────────────────────────
    public function assign(
        AssignIncidentRequest $request,
        Incident $incident
    ): JsonResponse {
        /** @var User $user */
        $user = Auth::user();

        $operatorId = $request->integer('operator_id') ?: $user->id;

        $operator = User::findOrFail($operatorId);

        if (! $operator->hasAnyRole(['operator', 'super_admin'])) {
            return response()->json([
                'message' => 'Assigned user must be an operator or super admin.',
            ], 422);
        }

        $incident->update(['assigned_operator_id' => $operator->id]);

        broadcast(new IncidentUpdated($incident->fresh()));

        return response()->json([
            'message'  => 'Incident assigned successfully.',
            'incident' => new IncidentResource(
                $incident->fresh()->load(['robot', 'assignedOperator', 'lockedBy'])
            ),
        ]);
    }

    // ─────────────────────────────────────────────
    // POST /api/incidents/{incident}/lock
    // Super admin locks incident after mitigation
    // ─────────────────────────────────────────────
    public function lock(
        LockIncidentRequest $request,
        Incident $incident
    ): JsonResponse {
        if ($incident->isLocked()) {
            return response()->json([
                'message' => 'This incident is already locked.',
            ], 422);
        }

        $incident->update([
            'is_locked' => true,
            'locked_by' => Auth::id(),
            'locked_at' => now(),
        ]);

        broadcast(new IncidentUpdated($incident->fresh()));

        return response()->json([
            'message'  => 'Incident locked successfully.',
            'incident' => new IncidentResource(
                $incident->fresh()->load(['robot', 'assignedOperator', 'lockedBy'])
            ),
        ]);
    }

    // ─────────────────────────────────────────────
    // POST /api/incidents/{incident}/unlock
    // Super admin unlocks if a correction is needed
    // ─────────────────────────────────────────────
    public function unlock(
        LockIncidentRequest $request,
        Incident $incident
    ): JsonResponse {
        if (! $incident->isLocked()) {
            return response()->json([
                'message' => 'This incident is not locked.',
            ], 422);
        }

        $incident->update([
            'is_locked' => false,
            'locked_by' => null,
            'locked_at' => null,
        ]);

        broadcast(new IncidentUpdated($incident->fresh()));

        return response()->json([
            'message'  => 'Incident unlocked successfully.',
            'incident' => new IncidentResource(
                $incident->fresh()->load(['robot', 'assignedOperator', 'lockedBy'])
            ),
        ]);
    }
}
