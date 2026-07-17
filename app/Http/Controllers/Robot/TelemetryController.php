<?php

namespace App\Http\Controllers\Robot;

use App\Http\Controllers\Controller;
use App\Http\Requests\Robot\StoreTelemetryRequest;
use App\Jobs\ProcessTelemetryJob;
use Illuminate\Http\JsonResponse;

class TelemetryController extends Controller
{
    public function store(StoreTelemetryRequest $request): JsonResponse
    {
        $robot = auth('sanctum')->user();
    
        $payload = $request->validated();
    
        // Handle image upload if present
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store(
                "robots/{$robot->id}/telemetry",
                'public'
            );
            $payload['image_path'] = $path;
        }
    
        ProcessTelemetryJob::dispatch(
            robotId:    $robot->id,
            payload:    $payload,
            receivedAt: now()->toIso8601String(),
        );
    
        return response()->json([
            'status'  => 'accepted',
            'message' => 'Telemetry received and queued for processing.',
        ], 202);
    }
}