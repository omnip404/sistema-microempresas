<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Bitacora;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Inicio de sesión (RF01). RF01.2: tras 3 intentos fallidos se bloquea el acceso 5 minutos.
 */
class LoginController extends Controller
{
    private const MAX_INTENTOS = 3;

    private const MINUTOS_BLOQUEO = 5;

    private const MENSAJE_FALLO = 'Correo o contraseña incorrectos. Si falló varias veces, espere unos minutos e intente de nuevo.';

    public function show()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credenciales = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $usuario = Usuario::where('email', $credenciales['email'])->first();

        if ($usuario?->estaBloqueado()) {
            // Mismo mensaje que un fallo normal: no se revela si el correo existe
            throw ValidationException::withMessages(['email' => self::MENSAJE_FALLO]);
        }

        if (! Auth::attempt($credenciales + ['activo' => true])) {
            $this->registrarIntentoFallido($usuario, $credenciales['email'], $request);

            throw ValidationException::withMessages(['email' => self::MENSAJE_FALLO]);
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

    private function registrarIntentoFallido(?Usuario $usuario, string $email, Request $request): void
    {
        // Rastro de detección: correo intentado e IP, nunca la contraseña
        $this->registrarFallo('LOGIN_FALLIDO', $usuario, $email, $request);

        if (! $usuario) {
            return;
        }

        // Incremento atómico: varios fallos simultáneos no deben pisarse el contador
        Usuario::whereKey($usuario->id)->increment('intentos_fallidos');
        $usuario->refresh();

        if ($usuario->intentos_fallidos >= self::MAX_INTENTOS) {
            $usuario->forceFill([
                'bloqueado_hasta' => now()->addMinutes(self::MINUTOS_BLOQUEO),
                'intentos_fallidos' => 0,
            ])->save();

            $this->registrarFallo('BLOQUEO', $usuario, $email, $request);
        }
    }

    private function registrarFallo(string $accion, ?Usuario $usuario, string $email, Request $request): void
    {
        Bitacora::create([
            'usuario_id' => null,
            'accion' => $accion,
            'tabla' => 'usuarios',
            'registro_id' => $usuario?->id,
            'detalle' => ['email' => Str::limit($email, 120, '')],
            'ip' => $request->ip(),
        ]);
    }
}
