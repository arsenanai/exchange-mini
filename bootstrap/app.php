<?php

use App\Exceptions\InsufficientFundsException;
use App\Exceptions\InvalidCredentialsException;
use App\Exceptions\OrderNotOpenException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Validation\UnauthorizedException;

$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);

        // Replace the default CSRF middleware with your application's version
        $middleware->replace(
            \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
        );
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->renderable(function (InsufficientFundsException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        });
        $exceptions->renderable(function (InvalidCredentialsException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        });
        $exceptions->renderable(function (OrderNotOpenException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        });
        $exceptions->renderable(function (UnauthorizedException $e) {
            return response()->json(['message' => 'This action is unauthorized.'], 403);
        });
    })
    ->create();

$app->booted(function () use ($app) {
    if ($app->environment(['local', 'testing'])) {
        require base_path('routes/testing.php');
    }
});

return $app;
