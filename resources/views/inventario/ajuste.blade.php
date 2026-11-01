@extends('layouts.app')

@section('titulo', 'Ajuste de inventario')

@section('contenido')
<form method="POST" action="{{ route('inventario.ajustar', $producto) }}" class="tarjeta p-3 p-md-4" style="max-width:640px" novalidate>
    @csrf
    <p class="mb-3"><strong>{{ $producto->nombre }}</strong> · stock actual: <span class="numero">{{ $producto->stock }}</span> {{ $producto->unidad_medida }}</p>

    <fieldset class="mb-3">
        <legend class="form-label">Operación</legend>
        @foreach (['sumar' => 'Sumar unidades', 'restar' => 'Restar unidades'] as $valor => $texto)
            <div class="form-check">
                <input class="form-check-input" type="radio" name="operacion" id="operacion-{{ $valor }}" value="{{ $valor }}"
                       @checked(old('operacion', 'sumar') === $valor)>
                <label class="form-check-label" for="operacion-{{ $valor }}">{{ $texto }}</label>
            </div>
        @endforeach
        @error('operacion') <div class="invalid-feedback d-block" id="error-operacion">{{ $message }}</div> @enderror
    </fieldset>

    <div class="mb-3">
        <label class="form-label" for="cantidad">Cantidad</label>
        <input class="form-control @error('cantidad') is-invalid @enderror" id="cantidad" name="cantidad" type="number" min="1" step="1"
               inputmode="numeric" value="{{ old('cantidad') }}" required
               @error('cantidad') aria-invalid="true" aria-describedby="error-cantidad" @enderror>
        @error('cantidad') <div class="invalid-feedback d-block" id="error-cantidad">{{ $message }}</div> @enderror
    </div>

    <div class="mb-3">
        <label class="form-label" for="observacion">Observación (obligatoria)</label>
        <input class="form-control @error('observacion') is-invalid @enderror" id="observacion" name="observacion" maxlength="200"
               value="{{ old('observacion') }}" required aria-describedby="ayuda-observacion @error('observacion') error-observacion @enderror"
               @error('observacion') aria-invalid="true" @enderror>
        <div class="form-text" id="ayuda-observacion">Explique el motivo: conteo físico, merma, producto dañado, etc.</div>
        @error('observacion') <div class="invalid-feedback d-block" id="error-observacion">{{ $message }}</div> @enderror
    </div>

    <div class="d-flex flex-wrap gap-2">
        <button class="btn btn-primario">Registrar ajuste</button>
        <a href="{{ route('inventario.movimientos', $producto) }}" class="btn btn-secundario">Cancelar</a>
    </div>
</form>
@endsection
