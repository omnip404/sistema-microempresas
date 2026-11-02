<?php

use App\Http\Middleware\VerificarCuentaActiva;
use App\Http\Middleware\VerificarRol;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias(['rol' => VerificarRol::class, 'activo' => VerificarCuentaActiva::class]);
        // Invalida las sesiones abiertas cuando cambia la contraseña de la cuenta
        $middleware->authenticateSessions();
        $middleware->redirectUsersTo('/ventas/nueva');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
