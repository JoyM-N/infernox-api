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
    /**
     * Mission commands that should not stack duplicates while active.
     *
     * @var list<string>
     */
    private const NON_STACKABLE = [
        'move_to',
        'suppress',
        'return_home',
        'activate_siren',
    ];

    /**
     * Teleop commands replaced by a newer press of the same type.
     *
     * @var list<string>
     */
    private const REPLACEABLE = [
        'drive',
        'arm_joint',
    ];

    // ─────────────────────────────────────────────
    // POST /api/robots/{robot}/commands
    // Operator sends a command to a robot
    // ─────────────────────────────────────────────
    public function send(
        SendCommandRequest $request,
        Robot $robot
    ): JsonResponse {
        if (! $robot->isAvailableForCommand()) {
            return response()->json([
                'message' => 'Robot is not available for commands.',
                'status'  => $robot->status->value,
            ], 422);
        }

        /** @var string $type */
        $type = (string) $request->validated('command_type');

        // Hard stop — cancel everything still in flight
        if (in_array($type, ['emergency_stop', 'stop'], true)) {
            $this->expireActiveCommands($robot);
        }

        // Teleop — replace any prior pending drive / arm command of same type
        if (in_array($type, self::REPLACEABLE, true)) {
            $this->expireActiveCommands($robot, $type);
        }

        // Mission commands — block duplicate active of same type
        if (in_array($type, self::NON_STACKABLE, true)) {
            $duplicate = RobotCommand::where('robot_id', $robot->id)
                ->where('command_type', $type)
                ->whereIn('status', ['pending', 'sent', 'acknowledged'])
                ->exists();

            if ($duplicate) {
                return response()->json([
                    'message' => "A {$type} command is already pending for this robot.",
                ], 422);
            }
        }

        $command = RobotCommand::create([
            'robot_id'     => $robot->id,
            'issued_by'    => Auth::id(),
            'incident_id'  => $request->validated('incident_id'),
            'command_type' => $type,
            'payload'      => $request->validated('payload'),
            'status'       => CommandStatus::PENDING,
            'issued_at'    => now(),
        ]);

        $command->load('issuedBy');

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
                fn ($q) => $q->where('status', $request->status)
            )
            ->with('issuedBy')
            ->orderBy('issued_at', 'desc')
            ->paginate(20);

        return CommandResource::collection($commands);
    }

    private function expireActiveCommands(Robot $robot, ?string $commandType = null): void
    {
        $commands = RobotCommand::where('robot_id', $robot->id)
            ->when($commandType, fn ($q) => $q->where('command_type', $commandType))
            ->whereIn('status', ['pending', 'sent', 'acknowledged'])
            ->with('issuedBy')
            ->get();

        foreach ($commands as $command) {
            $command->update(['status' => CommandStatus::EXPIRED]);
            broadcast(new CommandDispatched($command->fresh()->load('issuedBy')));
        }
    }
}
