@extends('layouts.app')

@section('titulo', $cliente->exists ? 'Editar cliente' : 'Nuevo cliente')

@php
    $campos = [
        ['nombre', 'Nombre', 'col-12', true],
        ['ci_nit', 'CI/NIT (opcional)', 'col-md-6', false],
        ['telefono', 'Teléfono (opcional)', 'col-md-6', false],
    ];
@endphp

@section('contenido')
<form method="POST" action="{{ $cliente->exists ? route('clientes.update', $cliente) : route('clientes.store') }}"
      class="tarjeta p-3 p-md-4" style="max-width:640px" novalidate>
    @csrf
    @if ($cliente->exists) @method('PUT') @endif

    <div class="row g-3">
        @foreach ($campos as [$campo, $etiqueta, $col, $obligatorio])
            <div class="{{ $col }}">
                <label class="form-label" for="{{ $campo }}">{{ $etiqueta }}</label>
                <input class="form-control @error($campo) is-invalid @enderror" id="{{ $campo }}" name="{{ $campo }}" type="text"
                       value="{{ old($campo, $cliente->$campo) }}" @if ($obligatorio) required @endif
                       @error($campo) aria-invalid="true" aria-describedby="error-{{ $campo }}" @enderror>
                @error($campo) <div class="invalid-feedback d-block" id="error-{{ $campo }}">{{ $message }}</div> @enderror
            </div>
        @endforeach
    </div>

    <div class="mt-4 d-flex flex-wrap gap-2">
        <button class="btn btn-primario">Guardar</button>
        <a href="{{ route('clientes.index') }}" class="btn btn-secundario">Cancelar</a>
    </div>
</form>
@endsection
