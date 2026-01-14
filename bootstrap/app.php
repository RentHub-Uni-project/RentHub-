<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use App\Http\Middleware\RoleMiddleware;


return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(prepend: [
            \App\Http\Middleware\CorsMiddleware::class,
            \App\Http\Middleware\EnsureJsonResponse::class,
        ]);

        $middleware->alias([
            'role' => RoleMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // $exceptions->render(function (AuthenticationException $e, Request $request) {
        //     if ($request->expectsJson()) {
        //         return response()->json(['message' => 'Unauthenticated.'], 401);
        //     }
        // });
        // $exceptions->render(function (Throwable $e, Request $request) {

        //     if ($request->expectsJson() || $request->is('api/*')) {

        //         $response = [
        //             'success' => false,
        //             'message' => $e->getMessage(),
        //         ];

        //         if (app()->environment('local')) {
        //             $response['debug'] = [
        //                 'message' => $e->getMessage(),
        //                 'file' => $e->getFile(),
        //                 'line' => $e->getLine(),
        //                 'exception' => get_class($e),
        //             ];
        //         }

        //         return response()->json($response);
        //     }

        //     return null;
        // });
    })->create();
