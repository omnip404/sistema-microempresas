<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Bitacora;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * Inicio de sesión (RF01). RF01.2: tras 3 intentos fallidos se bloquea el acceso 5 minutos.
 */
class LoginController extends Controller
{
    private const MAX_INTENTOS = 3;

    private const MINUTOS_BLOQUEO = 5;

    public function show()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credenciales = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $usuario = Usuario::where('email', $credenciales['email'])->first();

        if ($usuario?->estaBloqueado()) {
            throw ValidationException::withMessages([
                'email' => 'Cuenta bloqueada por intentos fallidos. Intente después de las '
                    .$usuario->bloqueado_hasta->format('H:i').'.',
            ]);
        }

        if (! Auth::attempt($credenciales + ['activo' => true])) {
            $this->registrarIntentoFallido($usuario);

            throw ValidationException::withMessages(['email' => 'Correo o contraseña incorrectos.']);
        }

        $usuario->forceFill(['intentos_fallidos' => 0, 'bloqueado_hasta' => null])->save();
        $request->session()->regenerate();
        $this->registrarEnBitacora('INGRESO', $usuario, $request);

        $destino = $usuario->tieneRol(Rol::ADMINISTRADOR) ? 'panel' : 'ventas.create';

        return redirect()->intended(route($destino));
    }

    public function logout(Request $request)
    {
        if ($usuario = $request->user()) {
            $this->registrarEnBitacora('SALIDA', $usuario, $request);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    /** Ingreso y salida no son cambios de datos, por eso no los registra el observador. */
    private function registrarEnBitacora(string $accion, Usuario $usuario, Request $request): void
    {
        Bitacora::create([
            'usuario_id' => $usuario->id,
            'accion' => $accion,
            'tabla' => $usuario->getTable(),
            'registro_id' => $usuario->id,
            'ip' => $request->ip(),
        ]);
    }

    private function registrarIntentoFallido(?Usuario $usuario): void
    {
        if (! $usuario) {
            return;
        }

        $usuario->intentos_fallidos++;

        if ($usuario->intentos_fallidos >= self::MAX_INTENTOS) {
            $usuario->bloqueado_hasta = now()->addMinutes(self::MINUTOS_BLOQUEO);
            $usuario->intentos_fallidos = 0;
        }

        $usuario->save();
    }
}
