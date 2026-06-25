<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CreateRobotRequest;
use App\Http\Requests\Api\UpdateRobotRequest;
use App\Http\Resources\RobotResource;
use App\Models\Robot;
use App\Services\RobotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class RobotController extends Controller
{
    public function __construct(private RobotService $robotService) {}

//GET/api/robots.;List robots with filters
    public function index(Request $request): AnonymousResourceCollection
    {
        $robots = Robot::query()
            ->with(['latestTelemetry', 'activeIncident'])
            ->when(
                $request->status,
                fn($q) => $q->where('status', $request->status)
            )
            ->when(
                $request->search,
                fn($q) => $q->where(function($q) use ($request) {
                    $q->where('name', 'like', "%{$request->search}%")
                      ->orWhere('serial_number', 'like', "%{$request->search}%");
                })
            )
            ->when(
                $request->boolean('low_battery'),
                fn($q) => $q->lowBattery()
            )
            ->orderBy('created_at', 'desc')
            ->paginate($request->integer('per_page', 20));

        return RobotResource::collection($robots);
    }

    // POST /api/robots;Provision a robot 
    public function store(CreateRobotRequest $request): JsonResponse
    {
        $robot = $this->robotService->provision($request->validated());

        return (new RobotResource($robot))
            ->response()
            ->setStatusCode(201);
    }


    // GET /api/robots/{robot}; Get a single robot's details
    public function show(Robot $robot): RobotResource
    {
        return new RobotResource(
            $robot->load(['latestTelemetry', 'activeIncident'])
        );
    }

    // PUT /api/robots/{robot}/; Update robot name or model
    public function update(UpdateRobotRequest $request, Robot $robot): RobotResource
    {
        $robot = $this->robotService->update($robot, $request->validated());
        return new RobotResource($robot);
    }

    // DELETE /api/robots/{robot}
    public function destroy(Robot $robot): JsonResponse
    {
        $this->robotService->decommission($robot);

        return response()->json([
            'message' => 'Robot decommissioned successfully.',
        ]);
    }

    // POST /api/robots/{robot}/rotate-token;Rotate a robot's API token
    public function rotateToken(Robot $robot): JsonResponse
    {
        $token = $this->robotService->rotateToken($robot);

        return response()->json([
            'message'   => 'Token rotated successfully. Store this — it will not be shown again.',
            'api_token' => $token,
        ]);
    }
}