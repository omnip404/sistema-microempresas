@extends('layouts.app')

@section('titulo', 'Reportes')

@section('contenido')
<form class="tarjeta p-3 mb-3" method="GET" action="{{ route('reportes.index') }}" aria-label="Elegir el periodo del reporte">
    <div class="row g-2 align-items-end">
        <div class="col-sm-4 col-md-3">
            <label class="form-label" for="desde">Desde</label>
            <input class="form-control @error('desde') is-invalid @enderror" type="date" id="desde" name="desde" value="{{ $desde->toDateString() }}" required
                   @error('desde') aria-invalid="true" aria-describedby="error-desde" @enderror>
            @error('desde') <div class="invalid-feedback d-block" id="error-desde">{{ $message }}</div> @enderror
        </div>
        <div class="col-sm-4 col-md-3">
            <label class="form-label" for="hasta">Hasta</label>
            <input class="form-control @error('hasta') is-invalid @enderror" type="date" id="hasta" name="hasta" value="{{ $hasta->toDateString() }}" required
                   @error('hasta') aria-invalid="true" aria-describedby="error-hasta" @enderror>
            @error('hasta') <div class="invalid-feedback d-block" id="error-hasta">{{ $message }}</div> @enderror
        </div>
        <div class="col-sm-4 col-md-6 d-flex flex-wrap gap-2">
            <button class="btn btn-primario">Ver reporte</button>
            <a class="btn btn-secundario" href="{{ route('reportes.exportar', ['desde' => $desde->toDateString(), 'hasta' => $hasta->toDateString()]) }}">Exportar a Excel (CSV)</a>
            <button type="button" class="btn btn-secundario" onclick="window.print()">Imprimir o guardar PDF</button>
        </div>
    </div>
</form>

<h2 class="h4">Ventas por día del {{ $desde->format('d/m/Y') }} al {{ $hasta->format('d/m/Y') }}</h2>
<p class="text-secundario">Solo se cuentan las ventas registradas; las anuladas no se incluyen.</p>

<div class="tarjeta p-2 p-md-3">
    <div class="table-responsive">
        <table class="table tabla">
            <caption class="visually-hidden">Ventas por día: cantidad de ventas, unidades vendidas y total en bolivianos</caption>
            <thead><tr>
                <th scope="col">Fecha</th><th scope="col" class="numero">N.º de ventas</th>
                <th scope="col" class="numero">Unidades</th><th scope="col" class="numero">Total (Bs)</th>
            </tr></thead>
            <tbody>
            @forelse ($reporte['filas'] as $f)
                <tr>
                    <th scope="row" class="fw-normal">{{ \Carbon\Carbon::parse($f['fecha'])->format('d/m/Y') }}</th>
                    <td class="numero">{{ $f['ventas'] }}</td>
                    <td class="numero">{{ $f['unidades'] }}</td>
                    <td class="numero">{{ number_format($f['total'], 2, ',', '.') }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-center text-secundario py-4">No hay ventas registradas en este periodo.</td></tr>
            @endforelse
            </tbody>
            <tfoot><tr class="fw-bold">
                <th scope="row">Total</th>
                <td class="numero">{{ $reporte['totales']['ventas'] }}</td>
                <td class="numero">{{ $reporte['totales']['unidades'] }}</td>
                <td class="numero">{{ number_format($reporte['totales']['total'], 2, ',', '.') }}</td>
            </tr></tfoot>
        </table>
    </div>
</div>
@endsection
