@extends('layouts.app')

@section('titulo', 'Usuarios')

@section('contenido')
<div class="mb-3">
    <a href="{{ route('usuarios.create') }}" class="btn btn-primario">Nuevo usuario</a>
</div>

<div class="tarjeta p-2 p-md-3">
    <div class="table-responsive">
        <table class="table tabla">
            <caption class="visually-hidden">Lista de usuarios con rol y estado de la cuenta</caption>
            <thead><tr>
                <th scope="col">Nombre</th><th scope="col">Correo</th><th scope="col">Rol</th>
                <th scope="col">Estado</th><th scope="col"><span class="visually-hidden">Acciones</span></th>
            </tr></thead>
            <tbody>
            @forelse ($usuarios as $u)
                <tr>
                    <td>{{ $u->nombre }}</td>
                    <td>{{ $u->email }}</td>
                    <td>{{ $u->rol->nombre }}</td>
                    <td>
                        @if (! $u->activo)
                            <span class="estado estado-neutro">Desactivado</span>
                        @elseif ($u->estaBloqueado())
                            <span class="estado estado-aviso">
                                <svg width="14" height="14" viewBox="0 0 14 14" aria-hidden="true"><path d="M7 1l6 11H1z" fill="none" stroke="currentColor" stroke-width="1.6"/><path d="M7 5v3.5M7 10v1" stroke="currentColor" stroke-width="1.6"/></svg>
                                Bloqueado hasta las {{ $u->bloqueado_hasta->format('H:i') }}
                            </span>
                        @else
                            <span class="estado estado-exito">Activo</span>
                        @endif
                    </td>
                    <td class="text-end">
                        <div class="d-flex justify-content-end flex-wrap gap-2">
                            <a href="{{ route('usuarios.edit', $u) }}" class="btn btn-sm btn-secundario">
                                Editar<span class="visually-hidden"> {{ $u->nombre }}</span>
                            </a>
                            @if ($u->estaBloqueado() || $u->intentos_fallidos > 0)
                                <form method="POST" action="{{ route('usuarios.desbloquear', $u) }}">
                                    @csrf
                                    <button class="btn btn-sm btn-secundario">
                                        Desbloquear<span class="visually-hidden"> a {{ $u->nombre }}</span>
                                    </button>
                                </form>
                            @endif
                            @unless ($u->activo === true && $u->is(auth()->user()))
                                <form method="POST" action="{{ route('usuarios.estado', $u) }}">
                                    @csrf
                                    <button class="btn btn-sm {{ $u->activo ? 'btn-peligro' : 'btn-secundario' }}">
                                        {{ $u->activo ? 'Desactivar' : 'Activar' }}<span class="visually-hidden"> a {{ $u->nombre }}</span>
                                    </button>
                                </form>
                            @endunless
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center text-secundario py-4">No hay usuarios registrados.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $usuarios->links() }}</div>
@endsection
