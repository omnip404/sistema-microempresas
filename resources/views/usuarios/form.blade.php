@extends('layouts.app')

@section('titulo', $usuario->exists ? 'Editar usuario' : 'Nuevo usuario')

@php
    $campos = [
        ['nombre', 'Nombre', 'text', 'col-md-6', null],
        ['email', 'Correo', 'email', 'col-md-6', null],
        ['password', $usuario->exists ? 'Nueva contraseña (opcional)' : 'Contraseña', 'password', 'col-md-6',
            $usuario->exists ? 'Déjela vacía para conservar la actual. Mínimo 8 caracteres.' : 'Mínimo 8 caracteres'],
    ];
@endphp

@section('contenido')
<form method="POST" action="{{ $usuario->exists ? route('usuarios.update', $usuario) : route('usuarios.store') }}"
      class="tarjeta p-3 p-md-4" style="max-width:760px" novalidate>
    @csrf
    @if ($usuario->exists) @method('PUT') @endif

    <div class="row g-3">
        @foreach ($campos as [$campo, $etiqueta, $tipo, $col, $ayuda])
            <div class="{{ $col }}">
                <label class="form-label" for="{{ $campo }}">{{ $etiqueta }}</label>
                <input class="form-control @error($campo) is-invalid @enderror" id="{{ $campo }}" name="{{ $campo }}" type="{{ $tipo }}"
                       @if ($tipo !== 'password') value="{{ old($campo, $usuario->$campo) }}" @else autocomplete="new-password" @endif
                       @if ($campo !== 'password' || ! $usuario->exists) required @endif
                       aria-describedby="{{ $ayuda ? "ayuda-$campo" : '' }} @error($campo) error-{{ $campo }} @enderror"
                       @error($campo) aria-invalid="true" @enderror>
                @if ($ayuda) <div class="form-text" id="ayuda-{{ $campo }}">{{ $ayuda }}</div> @endif
                @error($campo) <div class="invalid-feedback d-block" id="error-{{ $campo }}">{{ $message }}</div> @enderror
            </div>
        @endforeach

        <div class="col-md-6">
            <label class="form-label" for="rol_id">Rol</label>
            <select class="form-select @error('rol_id') is-invalid @enderror" id="rol_id" name="rol_id" required
                    @error('rol_id') aria-invalid="true" aria-describedby="error-rol_id" @enderror>
                @foreach ($roles as $r)
                    <option value="{{ $r->id }}" @selected(old('rol_id', $usuario->rol_id) == $r->id)>{{ $r->nombre }}</option>
                @endforeach
            </select>
            @error('rol_id') <div class="invalid-feedback d-block" id="error-rol_id">{{ $message }}</div> @enderror
        </div>

        <div class="col-12">
            <div class="form-check">
                <input type="hidden" name="activo" value="0">
                <input class="form-check-input" type="checkbox" name="activo" value="1" id="activo" @checked(old('activo', $usuario->activo))
                       @error('activo') aria-invalid="true" aria-describedby="error-activo" @enderror>
                <label class="form-check-label" for="activo">Cuenta activa (puede iniciar sesión)</label>
            </div>
            @error('activo') <div class="invalid-feedback d-block" id="error-activo">{{ $message }}</div> @enderror
        </div>
    </div>

    <div class="mt-4 d-flex flex-wrap gap-2">
        <button class="btn btn-primario">Guardar</button>
        <a href="{{ route('usuarios.index') }}" class="btn btn-secundario">Cancelar</a>
    </div>
</form>
@endsection
