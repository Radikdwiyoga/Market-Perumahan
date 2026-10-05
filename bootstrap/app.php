<?php

use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\StripBoostBrowserLogger;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        apiPrefix: 'api',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->throttleApi();

        // Percaya seluruh proxy membuat `Request::ip()` (dipakai sebagai kunci
        // rate limiter dan log audit) bisa dipalsukan lewat header
        // `X-Forwarded-For`. Isi `TRUSTED_PROXIES` dengan daftar proxy produksi
        // (mis. `10.0.0.1,10.0.0.2`) agar hanya proxy tepercaya yang dipakai.
        $middleware->trustProxies(at: (string) env('TRUSTED_PROXIES', '*'));

        // Alias, bukan middleware group: `active` harus dijalankan *setelah*
        // `auth`/`auth:sanctum` supaya `$request->user()` sudah terisi.
        $middleware->alias([
            'active' => EnsureUserIsActive::class,
        ]);

        // Hapus script Boost browser logger di non-local (ngrok dll)
        $middleware->appendToGroup('web', StripBoostBrowserLogger::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(function (AuthenticationException $exception, Request $request) {
            if ($request->is('api/*')) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }
        });

        $exceptions->render(function (ValidationException $exception, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => $exception->getMessage(),
                    'errors' => $exception->errors(),
                ], 422);
            }
        });
    })->create();
