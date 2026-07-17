<?php

namespace App\Http\Controllers\Api;

use App\Enums\CommandStatus;
use App\Events\CommandDispatched;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\SendCommandRequest;
use App\Http\Resources\CommandResource;
use App\Models\Robot;
use App\Models\RobotCommand;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CommandController extends Controller
{
    // ─────────────────────────────────────────────
    // POST /api/robots/{robot}/commands
    // Operator sends a command to a robot
    // ─────────────────────────────────────────────
    public function send(
        SendCommandRequest $request,
        Robot $robot
    ): JsonResponse {
        // Check robot can receive commands
        if (! $robot->isAvailableForCommand()) {
            return response()->json([
                'message' => 'Robot is not available for commands.',
                'status'  => $robot->status->value,
            ], 422);
        }

        // Prevent duplicate active commands of same type
        $duplicate = RobotCommand::where('robot_id', $robot->id)
            ->where('command_type', $request->command_type)
            ->whereIn('status', ['pending', 'sent', 'acknowledged'])
            ->exists();

        if ($duplicate) {
            return response()->json([
                'message' => "A {$request->command_type} command is already pending for this robot.",
            ], 422);
        }

        $command = RobotCommand::create([
            'robot_id'     => $robot->id,
            'issued_by'    => Auth::id(),
            'incident_id'  => $request->incident_id ?? null,
            'command_type' => $request->command_type,
            'payload'      => $request->payload ?? null,
            'status'       => CommandStatus::PENDING,
            'issued_at'    => now(),
        ]);
        // Broadcast to dashboard and robot channel
        broadcast(new CommandDispatched($command));

        return response()->json([
            'message' => 'Command dispatched successfully.',
            'command' => new CommandResource($command),
        ], 201);
    }

    // ─────────────────────────────────────────────
    // GET /api/robots/{robot}/commands
    // List all commands for a robot
    // ─────────────────────────────────────────────
    public function index(Request $request, Robot $robot): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $commands = RobotCommand::where('robot_id', $robot->id)
            ->when(
                $request->status,
                fn($q) => $q->where('status', $request->status)
            )
            ->with('issuedBy')
            ->orderBy('issued_at', 'desc')
            ->paginate(20);

        return CommandResource::collection($commands);
    }
}