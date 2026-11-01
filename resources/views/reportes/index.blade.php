@extends('layouts.app')

@section('titulo', 'Reportes')

@php
    $parametros = ['tipo' => $tipo, 'desde' => $desde->toDateString(), 'hasta' => $hasta->toDateString()];
    $formatear = fn ($valor, $formato) => match ($formato) {
        'fecha' => \Carbon\Carbon::parse($valor)->format('d/m/Y'),
        'monto' => number_format($valor, 2, ',', '.'),
        'entero' => number_format($valor, 0, ',', '.'),
        default => $valor,
    };
    $numericas = ['entero', 'monto'];
@endphp

@section('contenido')
<form class="tarjeta p-3 mb-3" method="GET" action="{{ route('reportes.index') }}" aria-label="Elegir el reporte y su periodo">
    <div class="row g-2 align-items-end">
        <div class="col-md-4">
            <label class="form-label" for="tipo">Tipo de reporte</label>
            <select class="form-select @error('tipo') is-invalid @enderror" id="tipo" name="tipo"
                    @error('tipo') aria-invalid="true" aria-describedby="error-tipo" @enderror>
                @foreach ($tipos as $clave => [$etiqueta])
                    <option value="{{ $clave }}" @selected($tipo === $clave)>{{ $etiqueta }}</option>
                @endforeach
            </select>
            @error('tipo') <div class="invalid-feedback d-block" id="error-tipo">{{ $message }}</div> @enderror
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label" for="desde">Desde</label>
            <input class="form-control @error('desde') is-invalid @enderror" type="date" id="desde" name="desde" value="{{ $desde->toDateString() }}" required
                   aria-describedby="ayuda-periodo @error('desde') error-desde @enderror" @error('desde') aria-invalid="true" @enderror>
            @error('desde') <div class="invalid-feedback d-block" id="error-desde">{{ $message }}</div> @enderror
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label" for="hasta">Hasta</label>
            <input class="form-control @error('hasta') is-invalid @enderror" type="date" id="hasta" name="hasta" value="{{ $hasta->toDateString() }}" required
                   aria-describedby="ayuda-periodo @error('hasta') error-hasta @enderror" @error('hasta') aria-invalid="true" @enderror>
            @error('hasta') <div class="invalid-feedback d-block" id="error-hasta">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-4 d-flex flex-wrap gap-2">
            <button class="btn btn-primario">Ver reporte</button>
            <a class="btn btn-secundario" href="{{ route('reportes.exportar', $parametros) }}">Exportar a Excel (CSV)</a>
            <button type="button" class="btn btn-secundario" onclick="window.print()">Imprimir o guardar PDF</button>
        </div>
    </div>
    <p class="form-text mb-0 mt-2" id="ayuda-periodo">Las fechas se aplican a «Ventas por día» y «Productos más vendidos»; los demás reportes muestran la situación actual.</p>
</form>

<h2 class="h4">
    {{ $tipos[$tipo][0] }}@if ($usaPeriodo) del {{ $desde->format('d/m/Y') }} al {{ $hasta->format('d/m/Y') }}@else al {{ now()->format('d/m/Y') }}@endif
</h2>
@if ($tipo === 'ventas' || $tipo === 'mas-vendidos')
    <p class="text-secundario">Solo se cuentan las ventas registradas; las anuladas no se incluyen.@if ($tipo === 'mas-vendidos') Se muestran los {{ \App\Services\ReporteService::LIMITE_MAS_VENDIDOS }} productos con más unidades.@endif</p>
@endif

<div class="tarjeta p-2 p-md-3">
    <div class="table-responsive">
        <table class="table tabla">
            <caption class="visually-hidden">{{ $tipos[$tipo][0] }}</caption>
            <thead><tr>
                @foreach ($reporte['columnas'] as [$clave, $etiqueta, $formato])
                    <th scope="col" @class(['numero' => in_array($formato, $numericas)])>{{ $etiqueta }}</th>
                @endforeach
            </tr></thead>
            <tbody>
            @forelse ($reporte['filas'] as $fila)
                <tr>
                    @foreach ($reporte['columnas'] as [$clave, $etiqueta, $formato])
                        @if ($loop->first && $formato === 'fecha')
                            <th scope="row" class="fw-normal">{{ $formatear($fila[$clave], $formato) }}</th>
                        @else
                            <td @class(['numero' => in_array($formato, $numericas)])>{{ $formatear($fila[$clave], $formato) }}</td>
                        @endif
                    @endforeach
                </tr>
            @empty
                <tr><td colspan="{{ count($reporte['columnas']) }}" class="text-center text-secundario py-4">
                    {{ $usaPeriodo ? 'No hay ventas registradas en este periodo.' : 'No hay productos para mostrar en este reporte.' }}
                </td></tr>
            @endforelse
            </tbody>
            @if ($reporte['totales'] !== null)
                <tfoot><tr class="fw-bold">
                    @foreach ($reporte['columnas'] as [$clave, $etiqueta, $formato])
                        @if ($loop->first)
                            <th scope="row">Total</th>
                        @else
                            <td @class(['numero' => in_array($formato, $numericas)])>
                                {{ isset($reporte['totales'][$clave]) ? $formatear($reporte['totales'][$clave], $formato) : '' }}
                            </td>
                        @endif
                    @endforeach
                </tr></tfoot>
            @endif
        </table>
    </div>
</div>
@endsection
