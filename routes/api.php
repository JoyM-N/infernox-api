<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

// PUBLIC ROUTES — no authentication required
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

// AUTHENTICATED ROUTES — human operators
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
});

// ROBOT ROUTES — authenticated via robot API token
Route::prefix('robot')->group(function () {
    // Robot routes will go here in Phase 4
});