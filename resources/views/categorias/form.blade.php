@extends('layouts.app')

@section('titulo', $categoria->exists ? 'Editar categoría' : 'Nueva categoría')

@php
    $campos = [
        ['nombre', 'Nombre', 'Máximo 60 caracteres'],
        ['descripcion', 'Descripción (opcional)', 'Máximo 150 caracteres'],
    ];
@endphp

@section('contenido')
<form method="POST" action="{{ $categoria->exists ? route('categorias.update', $categoria) : route('categorias.store') }}"
      class="tarjeta p-3 p-md-4" style="max-width:560px" novalidate>
    @csrf
    @if ($categoria->exists) @method('PUT') @endif

    <div class="row g-3">
        @foreach ($campos as [$campo, $etiqueta, $ayuda])
            <div class="col-12">
                <label class="form-label" for="{{ $campo }}">{{ $etiqueta }}</label>
                <input class="form-control @error($campo) is-invalid @enderror" id="{{ $campo }}" name="{{ $campo }}" type="text"
                       value="{{ old($campo, $categoria->$campo) }}" @if ($campo === 'nombre') required @endif
                       aria-describedby="ayuda-{{ $campo }} @error($campo) error-{{ $campo }} @enderror"
                       @error($campo) aria-invalid="true" @enderror>
                <div class="form-text" id="ayuda-{{ $campo }}">{{ $ayuda }}</div>
                @error($campo) <div class="invalid-feedback d-block" id="error-{{ $campo }}">{{ $message }}</div> @enderror
            </div>
        @endforeach
    </div>

    <div class="mt-4 d-flex flex-wrap gap-2">
        <button class="btn btn-primario">Guardar</button>
        <a href="{{ route('categorias.index') }}" class="btn btn-secundario">Cancelar</a>
    </div>
</form>
@endsection
