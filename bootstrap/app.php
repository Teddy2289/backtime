<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\HandleCors;
use Illuminate\Http\Request;
use Symfony\Component\Routing\Exception\RouteNotFoundException;


return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {

        // Middleware API (globaux pour toutes les routes API)
        $middleware->api([
            \Illuminate\Http\Middleware\HandleCors::class,            // OBLIGATOIRE
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ]);

        // Middleware du groupe "api"
        $middleware->group('api', [
            \Illuminate\Routing\Middleware\ThrottleRequests::class . ':api',
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ]);

        // Alias de middlewares
        $middleware->alias([
            'user.middleware' => \Modules\User\Middleware\UserMiddleware::class,
            'auth.api' => \App\Http\Middleware\Authenticate::class,
            'auth' => \App\Http\Middleware\Authenticate::class,
            'refresh.token' => \App\Http\Middleware\RefreshToken::class,
        ]);

        // Prepend global (gardé)
        $middleware->prepend(HandleCors::class);
    })

    ->withExceptions(function (Exceptions $exceptions): void {
        // Personnaliser les exceptions pour l'API
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated.',
                    'error' => 'AUTH_001'
                ], 401);
            }
        });

        $exceptions->render(function (RouteNotFoundException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Route not found.',
                    'error' => 'ROUTE_001'
                ], 404);
            }
        });
    })->create();