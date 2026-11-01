<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Control de acceso por rol (RF02). Uso en rutas: ->middleware('rol:Administrador')
 */
class VerificarRol
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $usuario = $request->user();

        if (! $usuario || ! $usuario->tieneRol(...$roles)) {
            abort(403, 'No tiene permiso para acceder a esta opción.');
        }

        return $next($request);
    }
}
