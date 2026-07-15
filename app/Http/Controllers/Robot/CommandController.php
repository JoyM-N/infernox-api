<?php

namespace App\Http\Controllers\Robot;

use App\Http\Controllers\Controller;
use App\Http\Resources\CommandResource;
use App\Models\RobotCommand;
use App\Enums\CommandStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommandController extends Controller
{
    // ─────────────────────────────────────────────
    // GET /api/robot/commands/pending
    // Robot polls this to get its pending commands
    // ─────────────────────────────────────────────
    public function pending(Request $request): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $robot = auth('sanctum')->user();

        // Get all pending commands for this robot
        // Mark them as 'sent' since robot is now aware of them
        $commands = RobotCommand::where('robot_id', $robot->id)
            ->where('status', CommandStatus::PENDING)
            ->get();

        // Mark as sent
        foreach ($commands as $command) {
            $command->update(['status' => CommandStatus::SENT]);
        }

        return CommandResource::collection($commands);
    }

    // ─────────────────────────────────────────────
    // PATCH /api/robot/commands/{command}/acknowledge
    // Robot tells us it received and executed a command
    // ─────────────────────────────────────────────
    public function acknowledge(Request $request, RobotCommand $command): JsonResponse
    {
        $robot = auth('sanctum')->user();

        // Security — make sure this command belongs to THIS robot
        if ($command->robot_id !== $robot->id) {
            return response()->json([
                'message' => 'This command does not belong to your robot.',
            ], 403);
        }

        // Prevent updating a terminal status
        if ($command->status->isTerminal()) {
            return response()->json([
                'message' => 'This command has already been completed.',
                'status'  => $command->status->value,
            ], 409);
        }

        $status = $request->input('status', 'executed');

        $command->update([
            'status'           => CommandStatus::from($status),
            'acknowledged_at'  => $command->acknowledged_at ?? now(),
            'executed_at'      => $status === 'executed' ? now() : null,
        ]);

        return response()->json([
            'message' => 'Command acknowledged.',
            'status'  => $command->status->value,
        ]);
    }
}