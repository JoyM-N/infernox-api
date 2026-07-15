<?php

namespace App\Http\Controllers\Robot;

use App\Http\Controllers\Controller;
use App\Http\Requests\Robot\StoreTelemetryRequest;
use App\Jobs\ProcessTelemetryJob;
use Illuminate\Http\JsonResponse;

class TelemetryController extends Controller
{
    // ─────────────────────────────────────────────
    // POST /api/robot/telemetry
    // Called by: the robot every 3 seconds
    // Auth: robot API token
    //
    // This endpoint must be FAST
    // We do the minimum work here:
    //   1. Validate the data
    //   2. Dispatch a background job
    //   3. Return 202 Accepted immediately
    //
    // The actual processing happens in
    // ProcessTelemetryJob — not here
    // This means the robot never waits
    // ─────────────────────────────────────────────
    public function store(StoreTelemetryRequest $request): JsonResponse
    {

        $robot = auth('sanctum')->user();

        ProcessTelemetryJob::dispatch(
            robotId:    $robot->id,
            payload:    $request->validated(),
            receivedAt: now()->toIso8601String(),
        );

        return response()->json([
            'status'  => 'accepted',
            'message' => 'Telemetry received and queued for processing.',
        ], 202);
    }
}