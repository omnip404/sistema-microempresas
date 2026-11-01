@extends('layouts.app')

@section('titulo', $proveedor->exists ? 'Editar proveedor' : 'Nuevo proveedor')

@php
    $campos = [
        ['nombre', 'Nombre', 'col-md-8', true, null],
        ['nit', 'NIT (opcional)', 'col-md-4', false, 'Debe ser único entre proveedores'],
        ['telefono', 'Teléfono', 'col-md-4', false, null],
        ['direccion', 'Dirección', 'col-md-8', false, null],
    ];
@endphp

@section('contenido')
<form method="POST" action="{{ $proveedor->exists ? route('proveedores.update', $proveedor) : route('proveedores.store') }}"
      class="tarjeta p-3 p-md-4" style="max-width:760px" novalidate>
    @csrf
    @if ($proveedor->exists) @method('PUT') @endif

    <div class="row g-3">
        @foreach ($campos as [$campo, $etiqueta, $col, $obligatorio, $ayuda])
            <div class="{{ $col }}">
                <label class="form-label" for="{{ $campo }}">{{ $etiqueta }}</label>
                <input class="form-control @error($campo) is-invalid @enderror" id="{{ $campo }}" name="{{ $campo }}" type="text"
                       value="{{ old($campo, $proveedor->$campo) }}" @if ($obligatorio) required @endif
                       aria-describedby="{{ $ayuda ? "ayuda-$campo" : '' }} @error($campo) error-{{ $campo }} @enderror"
                       @error($campo) aria-invalid="true" @enderror>
                @if ($ayuda) <div class="form-text" id="ayuda-{{ $campo }}">{{ $ayuda }}</div> @endif
                @error($campo) <div class="invalid-feedback d-block" id="error-{{ $campo }}">{{ $message }}</div> @enderror
            </div>
        @endforeach

        <div class="col-12">
            <div class="form-check">
                <input type="hidden" name="activo" value="0">
                <input class="form-check-input" type="checkbox" name="activo" value="1" id="activo" @checked(old('activo', $proveedor->activo))>
                <label class="form-check-label" for="activo">Proveedor activo (disponible para registrar compras)</label>
            </div>
        </div>
    </div>

    <div class="mt-4 d-flex flex-wrap gap-2">
        <button class="btn btn-primario">Guardar</button>
        <a href="{{ route('proveedores.index') }}" class="btn btn-secundario">Cancelar</a>
    </div>
</form>
@endsection
