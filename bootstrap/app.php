<?php

use App\Http\Middleware\EnsurePasswordWasChanged;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // En prod derrière Cloud/LB, pinez les proxies via TRUSTED_PROXIES
        // (IPs séparées par virgule). '*' par défaut = comportement actuel.
        $trusted = trim((string) env('TRUSTED_PROXIES', '*'));
        $middleware->trustProxies(at: $trusted === '' || $trusted === '*' ? '*' : array_values(array_filter(array_map(fn (string $ip): string => trim($ip), explode(',', $trusted)))));

        $middleware->alias([
            'password.changed' => EnsurePasswordWasChanged::class,
        ]);

        $middleware->append(SecurityHeaders::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
