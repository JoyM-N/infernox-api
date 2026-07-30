<?php

namespace App\Http\Controllers\Robot;

use App\Enums\CommandStatus;
use App\Events\CommandDispatched;
use App\Http\Controllers\Controller;
use App\Http\Resources\CommandResource;
use App\Models\Robot;
use App\Models\RobotCommand;
use App\Services\CommandLifecycleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommandController extends Controller
{
    public function __construct(
        private CommandLifecycleService $lifecycle
    ) {}

    // ─────────────────────────────────────────────
    // GET /api/robot/commands/pending
    // Robot polls this to get its pending commands
    // ─────────────────────────────────────────────
    public function pending(Request $request): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        /** @var Robot $robot */
        $robot = auth('sanctum')->user();

        // Clean up abandoned commands so the dashboard cannot stay on Pending forever
        $this->lifecycle->expireStale();

        $commands = RobotCommand::where('robot_id', $robot->id)
            ->where('status', CommandStatus::PENDING)
            ->with('issuedBy')
            ->orderBy('issued_at')
            ->get();

        foreach ($commands as $command) {
            $command->update(['status' => CommandStatus::SENT]);
            broadcast(new CommandDispatched($command->fresh()->load('issuedBy')));
        }

        return CommandResource::collection($commands);
    }

    // ─────────────────────────────────────────────
    // PATCH /api/robot/commands/{command}/acknowledge
    // Robot tells us it received and executed a command
    // ─────────────────────────────────────────────
    public function acknowledge(Request $request, RobotCommand $command): JsonResponse
    {
        /** @var Robot $robot */
        $robot = auth('sanctum')->user();

        if ($command->robot_id !== $robot->id) {
            return response()->json([
                'message' => 'This command does not belong to your robot.',
            ], 403);
        }

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

        broadcast(new CommandDispatched($command->fresh()->load('issuedBy')));

        return response()->json([
            'message' => 'Command acknowledged.',
            'status'  => $command->status->value,
        ]);
    }
}
