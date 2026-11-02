@extends('layouts.app')

@section('titulo', 'Ventas')

@section('contenido')
<div class="tarjeta p-2 p-md-3">
    <div class="table-responsive">
        <table class="table tabla">
            <caption class="visually-hidden">Ventas registradas, de la más reciente a la más antigua</caption>
            <thead><tr>
                <th scope="col">N°</th><th scope="col">Fecha</th><th scope="col">Vendedor</th><th scope="col">Cliente</th>
                <th scope="col" class="numero">Total (Bs)</th><th scope="col">Estado</th><th scope="col">Comprobante</th><th scope="col">Anular</th>
            </tr></thead>
            <tbody>
            @forelse ($ventas as $v)
                <tr>
                    <td>{{ $v->id }}</td>
                    <td>{{ $v->fecha->format('d/m/Y H:i') }}</td>
                    <td>{{ $v->usuario->nombre }}</td>
                    <td>{{ $v->cliente->nombre ?? 'Sin cliente' }}</td>
                    <td class="numero">{{ number_format($v->total, 2, ',', '.') }}</td>
                    <td>
                        @if ($v->estado === 'ANULADA')
                            <span class="estado estado-neutro">Anulada</span>
                        @else
                            <span class="estado estado-exito">Registrada</span>
                        @endif
                    </td>
                    <td><a href="{{ route('ventas.comprobante', $v) }}" target="_blank" rel="noopener">Imprimir comprobante<span class="visually-hidden"> de la venta {{ $v->id }}</span></a></td>
                    <td>
                        @if ($v->estado === 'REGISTRADA')
                            <form method="POST" action="{{ route('ventas.anular', $v) }}" class="d-flex gap-2"
                                  onsubmit="return confirm('¿Anular la venta N° {{ $v->id }}? El stock volverá al inventario.')">
                                @csrf
                                <label class="visually-hidden" for="motivo-{{ $v->id }}">Motivo de anulación de la venta {{ $v->id }}</label>
                                <input class="form-control" style="min-width:160px" id="motivo-{{ $v->id }}" name="motivo" placeholder="Motivo" required>
                                <button class="btn btn-peligro">Anular</button>
                            </form>
                        @else
                            <span class="text-secundario small">{{ $v->motivo_anulacion }}</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-center text-secundario py-4">Todavía no hay ventas registradas.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $ventas->links() }}</div>
@endsection
