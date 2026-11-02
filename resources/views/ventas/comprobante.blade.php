@extends('layouts.app')

@section('titulo', 'Comprobante de venta N° '.$venta->id)

@section('contenido')
<div class="tarjeta p-3 p-md-4" style="max-width:720px">
    <header class="text-center mb-3">
        <p class="h4 mb-0">{{ config('app.name') }}</p>
        <p class="mb-0">Comprobante de venta N° {{ $venta->id }}</p>
    </header>

    <dl class="row mb-3">
        <dt class="col-sm-3">Fecha</dt><dd class="col-sm-9">{{ $venta->fecha->format('d/m/Y H:i') }}</dd>
        <dt class="col-sm-3">Cliente</dt><dd class="col-sm-9">{{ $venta->cliente->nombre ?? 'Sin cliente' }}</dd>
        <dt class="col-sm-3">Vendedor</dt><dd class="col-sm-9">{{ $venta->usuario->nombre }}</dd>
        <dt class="col-sm-3">Estado</dt>
        <dd class="col-sm-9">
            @if ($venta->estado === 'ANULADA')
                <span class="estado estado-neutro">Anulada</span>
            @else
                <span class="estado estado-exito">Registrada</span>
            @endif
        </dd>
    </dl>

    <div class="table-responsive">
        <table class="table tabla">
            <caption class="visually-hidden">Detalle de la venta N° {{ $venta->id }}</caption>
            <thead><tr>
                <th scope="col">Producto</th><th scope="col" class="numero">Cantidad</th>
                <th scope="col" class="numero">Precio unitario (Bs)</th><th scope="col" class="numero">Subtotal (Bs)</th>
            </tr></thead>
            <tbody>
            @foreach ($venta->detalles as $d)
                <tr>
                    <td>{{ $d->producto->nombre }}</td>
                    <td class="numero">{{ $d->cantidad }}</td>
                    <td class="numero">{{ number_format($d->precio_unitario, 2, ',', '.') }}</td>
                    <td class="numero">{{ number_format($d->subtotal(), 2, ',', '.') }}</td>
                </tr>
            @endforeach
            </tbody>
            <tfoot><tr>
                <th scope="row" colspan="3" class="numero">Total (Bs)</th>
                <td class="numero fw-bold">{{ number_format($venta->total, 2, ',', '.') }}</td>
            </tr></tfoot>
        </table>
    </div>

    @if ($venta->estado === 'ANULADA')
        <p class="text-secundario small">Motivo de anulación: {{ $venta->motivo_anulacion }}</p>
    @endif

    <button type="button" class="btn btn-primario" onclick="window.print()">Imprimir comprobante</button>
</div>
@endsection
