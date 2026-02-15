<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // REGISTRO DEL ALIAS PARA TUS RUTAS
        $middleware->alias([
            'role' => \App\Common\Http\Middleware\RoleMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Sesion expirada: redirigir al login con mensaje amigable
        $exceptions->renderable(function (\Illuminate\Session\TokenMismatchException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json(['msg' => 'Sesion expirada.', 'errorCode' => 419], 419);
            }

            return redirect()->route('login')
                ->with('error', 'Tu sesion ha expirado. Por favor inicia sesion nuevamente.');
        });
    })->create();
