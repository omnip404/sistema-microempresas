@extends('layouts.app')

@section('titulo', $producto->exists ? 'Editar producto' : 'Nuevo producto')

@php
    // Campo de texto con su etiqueta, ayuda y error asociados para lectores de pantalla
    $campos = [
        ['codigo', 'Código', 'text', 'col-md-4', null],
        ['nombre', 'Nombre', 'text', 'col-md-8', null],
        ['unidad_medida', 'Unidad de medida', 'text', 'col-md-6', 'Por ejemplo: unidad, bolsa, kg, litro'],
        ['precio_compra', 'Precio de compra (Bs)', 'number', 'col-md-4', null],
        ['precio_venta', 'Precio de venta (Bs)', 'number', 'col-md-4', null],
        ['stock_minimo', 'Stock mínimo', 'number', 'col-md-4', 'Debajo de este número se muestra la alerta'],
    ];
@endphp

@section('contenido')
<form method="POST" action="{{ $producto->exists ? route('productos.update', $producto) : route('productos.store') }}"
      class="tarjeta p-3 p-md-4" style="max-width:760px" novalidate>
    @csrf
    @if ($producto->exists) @method('PUT') @endif

    <div class="row g-3">
        @foreach ($campos as [$campo, $etiqueta, $tipo, $col, $ayuda])
            <div class="{{ $col }}">
                <label class="form-label" for="{{ $campo }}">{{ $etiqueta }}</label>
                <input class="form-control @error($campo) is-invalid @enderror" id="{{ $campo }}" name="{{ $campo }}" type="{{ $tipo }}"
                       @if ($tipo === 'number') min="0" step="{{ str_starts_with($campo, 'precio') ? '0.01' : '1' }}" inputmode="decimal" @endif
                       value="{{ old($campo, $producto->$campo) }}" required
                       aria-describedby="{{ $ayuda ? "ayuda-$campo" : '' }} @error($campo) error-{{ $campo }} @enderror"
                       @error($campo) aria-invalid="true" @enderror>
                @if ($ayuda) <div class="form-text" id="ayuda-{{ $campo }}">{{ $ayuda }}</div> @endif
                @error($campo) <div class="invalid-feedback d-block" id="error-{{ $campo }}">{{ $message }}</div> @enderror
            </div>

            @if ($campo === 'nombre')
                <div class="col-md-6">
                    <label class="form-label" for="categoria_id">Categoría</label>
                    <select class="form-select" id="categoria_id" name="categoria_id" required>
                        @foreach ($categorias as $c)
                            <option value="{{ $c->id }}" @selected(old('categoria_id', $producto->categoria_id) == $c->id)>{{ $c->nombre }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
        @endforeach

        <div class="col-12">
            <div class="form-check">
                <input type="hidden" name="activo" value="0">
                <input class="form-check-input" type="checkbox" name="activo" value="1" id="activo" @checked(old('activo', $producto->activo))>
                <label class="form-check-label" for="activo">Producto activo (disponible para la venta)</label>
            </div>
        </div>
    </div>

    <div class="mt-4 d-flex flex-wrap gap-2">
        <button class="btn btn-primario">Guardar</button>
        <a href="{{ route('productos.index') }}" class="btn btn-secundario">Cancelar</a>
    </div>
</form>
@endsection
