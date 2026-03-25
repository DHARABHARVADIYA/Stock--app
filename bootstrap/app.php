<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Auth\AuthenticationException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\AuthOnly;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'admin'        => AdminMiddleware::class,
            'auth.only'    => AuthOnly::class,
            'sales'        => \App\Http\Middleware\SalesMiddleware::class,
            'check.active' => \App\Http\Middleware\CheckActiveUser::class,
            'dispatcher' => \App\Http\Middleware\DispatcherMiddleware::class,
            'role'         => \App\Http\Middleware\RoleMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {

        // 🔴 TOKEN NOT PROVIDED
        $exceptions->render(function (AuthenticationException $e, $request) {
            return response()->json([
                'status'  => 401,
                'message' => 'Token not provided',
                'result'  => null
            ], 401);
        });

        // 🔴 TOKEN EXPIRED
        $exceptions->render(function (TokenExpiredException $e, $request) {
            return response()->json([
                'status'  => 401,
                'message' => 'Token expired',
                'result'  => null
            ], 401);
        });

        // 🔴 TOKEN INVALID
        $exceptions->render(function (TokenInvalidException $e, $request) {
            return response()->json([
                'status'  => 401,
                'message' => 'Token invalid',
                'result'  => null
            ], 401);
        });

    })
    ->create();
