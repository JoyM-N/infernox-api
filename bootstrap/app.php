<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            \Illuminate\Support\Facades\Broadcast::routes([
                'middleware' => ['auth:sanctum'],
            ]);

            require __DIR__.'/../routes/channels.php';
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->is('broadcasting/*'),
        );
        // Tell Sanctum to return JSON 401 instead of
    // redirecting to a named 'login' route that doesn't exist
    $exceptions->render(function (
        \Illuminate\Auth\AuthenticationException $e,
        Request $request
    ) {
        if ($request->is('api/*') || $request->is('broadcasting/*')) {
            return response()->json([
                'message' => 'Unauthenticated. Please login first.',
            ], 401);
        }
    });
    })->create();
