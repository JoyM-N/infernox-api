<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CommandController;
use App\Http\Controllers\Api\IncidentController;
use App\Http\Controllers\Api\RobotController;
use App\Http\Controllers\Robot\TelemetryController;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

// ─────────────────────────────────────────────
// PUBLIC ROUTES
// ─────────────────────────────────────────────
Route::get('/health', function () {
    try {
        DB::connection()->getPdo();
        $database = 'connected';
    } catch (\Exception $e) {
        $database = 'failed: ' . $e->getMessage();
    }

    try {
        Cache::store('redis')->put('health_check', 'ok', 10);
        $redis = 'connected';
    } catch (\Exception $e) {
        $redis = 'failed: ' . $e->getMessage();
    }

    return response()->json([
        'status'    => 'ok',
        'service'   => 'INFERNOX API',
        'version'   => '1.0.0',
        'checks'    => [
            'database' => $database,
            'redis'    => $redis,
        ],
        'timestamp' => now()->toIso8601String(),
    ]);
});

Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
});

// ─────────────────────────────────────────────
// HUMAN OPERATOR ROUTES
// ─────────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::prefix('auth')->group(function () {
        Route::post('/logout',   [AuthController::class, 'logout']);
        Route::get('/me',        [AuthController::class, 'me']);
        Route::post('/register', [AuthController::class, 'register']);
    });

    // Robots
    Route::apiResource('robots', RobotController::class);
    Route::post('robots/{robot}/rotate-token', [RobotController::class, 'rotateToken'])
         ->name('robots.rotate-token');

    // Commands — send to robot, list for robot
    Route::post('robots/{robot}/commands', [CommandController::class, 'send'])
         ->name('robots.commands.send');
    Route::get('robots/{robot}/commands',  [CommandController::class, 'index'])
         ->name('robots.commands.index');

    // Incidents
    Route::apiResource('incidents', IncidentController::class)->only([
        'index', 'show', 'update'
    ]);
    Route::post('incidents/{incident}/updates', [IncidentController::class, 'addUpdate'])
         ->name('incidents.updates.store');

});

// ─────────────────────────────────────────────
// ROBOT ROUTES — robot token only
// ─────────────────────────────────────────────
Route::prefix('robot')->middleware('auth:sanctum')->group(function () {

    // Telemetry
    Route::post('/telemetry', [TelemetryController::class, 'store'])
         ->name('robot.telemetry.store');

    // Commands
    Route::get('/commands/pending', [\App\Http\Controllers\Robot\CommandController::class, 'pending'])
         ->name('robot.commands.pending');

    Route::patch('/commands/{command}/acknowledge', [\App\Http\Controllers\Robot\CommandController::class, 'acknowledge'])
         ->name('robot.commands.acknowledge');

});