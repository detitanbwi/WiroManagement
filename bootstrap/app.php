<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
            'permission' => \App\Http\Middleware\PermissionMiddleware::class,
            'internal' => \App\Http\Middleware\EnsureInternalUser::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Handle CSRF Token Mismatch & Session Expired (HTTP 419)
        $exceptions->render(function (\Illuminate\Session\TokenMismatchException $e, $request) {
            // Only regenerate the CSRF token — do NOT invalidate the session.
            // Invalidating here caused a vicious loop: each redirect started a
            // brand-new session whose token never matched the next POST request.
            if ($request->hasSession()) {
                $request->session()->regenerateToken();
            }

            if ($request->expectsJson()) {
                return response()->json(['message' => 'Sesi telah berakhir. Silakan login kembali.'], 419);
            }

            return redirect()->route('login')->with('error', 'Sesi Anda telah berakhir demi keamanan. Silakan login kembali.');
        });

        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpException $e, $request) {
            if ($e->getStatusCode() === 419) {
                if ($request->hasSession()) {
                    $request->session()->regenerateToken();
                }

                if ($request->expectsJson()) {
                    return response()->json(['message' => 'Sesi telah berakhir. Silakan login kembali.'], 419);
                }

                return redirect()->route('login')->with('error', 'Sesi Anda telah berakhir demi keamanan. Silakan login kembali.');
            }

            if ($e->getStatusCode() === 403) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'status' => 'error',
                        'message' => $e->getMessage() ?: 'Akses ditolak. Anda tidak memiliki izin untuk tindakan ini.',
                    ], 403);
                }

                return response()->view('errors.403', ['exception' => $e], 403);
            }
        });
    })->create();
