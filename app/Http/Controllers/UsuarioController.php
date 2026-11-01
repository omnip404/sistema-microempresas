<?php

namespace App\Http\Controllers;

use App\Http\Requests\UsuarioRequest;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Http\Request;

/**
 * Gestión de usuarios y roles (RF02). Solo Administrador.
 */
class UsuarioController extends Controller
{
    public function index()
    {
        return view('usuarios.index', [
            'usuarios' => Usuario::with('rol')->orderBy('nombre')->paginate(15),
        ]);
    }

    public function create()
    {
        return view('usuarios.form', [
            'usuario' => new Usuario(['activo' => true]),
            'roles' => Rol::orderBy('nombre')->get(),
        ]);
    }

    public function store(UsuarioRequest $request)
    {
        Usuario::create($request->validated());

        return redirect()->route('usuarios.index')->with('ok', 'Usuario registrado.');
    }

    public function edit(Usuario $usuario)
    {
        return view('usuarios.form', [
            'usuario' => $usuario,
            'roles' => Rol::orderBy('nombre')->get(),
        ]);
    }

    public function update(UsuarioRequest $request, Usuario $usuario)
    {
        $datos = $request->validated();

        // La contraseña solo cambia si se escribió una nueva
        if (blank($datos['password'] ?? null)) {
            unset($datos['password']);
        }

        $usuario->update($datos);

        return redirect()->route('usuarios.index')->with('ok', 'Usuario actualizado.');
    }

    /** Activa o desactiva la cuenta. Un administrador no puede desactivarse a sí mismo. */
    public function cambiarEstado(Request $request, Usuario $usuario)
    {
        if ($usuario->activo && $usuario->is($request->user())) {
            return back()->withErrors(['usuario' => 'No puede desactivar su propia cuenta.']);
        }

        $usuario->update(['activo' => ! $usuario->activo]);

        return back()->with('ok', $usuario->activo ? "Usuario {$usuario->nombre} activado." : "Usuario {$usuario->nombre} desactivado.");
    }

    /** Quita el bloqueo por intentos fallidos (RF01.2). */
    public function desbloquear(Usuario $usuario)
    {
        $usuario->forceFill(['intentos_fallidos' => 0, 'bloqueado_hasta' => null])->save();

        return back()->with('ok', "Cuenta de {$usuario->nombre} desbloqueada.");
    }
}
