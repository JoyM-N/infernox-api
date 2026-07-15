<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Http\Controllers\Api\RobotController;

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

// Auth routes — public, no token needed
Route::prefix('auth')->group(function () {
    Route::post('/login',  [AuthController::class, 'login']);
});


// PROTECTED ROUTES — requires valid Bearer token

Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::prefix('auth')->group(function () {
        Route::post('/logout',   [AuthController::class, 'logout']);
        Route::get('/me',        [AuthController::class, 'me']);
        Route::post('/register', [AuthController::class, 'register']);
    });
    // Robot routes
    Route::apiResource('robots', RobotController::class);
    Route::post('robots/{robot}/rotate-token', [RobotController::class, 'rotateToken'])
         ->name('robots.rotate-token');

});

// ROBOT ROUTES — robot token authentication

Route::prefix('robot')->group(function () {
    // Robot routes coming in Phase 4
});

// ROBOT ROUTES — robot token authentication
Route::prefix('robot')->middleware('auth:sanctum')->group(function () {

    // Robot submits sensor data
    Route::post('/telemetry', [
        \App\Http\Controllers\Robot\TelemetryController::class,
        'store'
    ])->name('robot.telemetry.store');

});