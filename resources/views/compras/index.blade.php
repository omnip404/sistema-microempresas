@extends('layouts.app')

@section('titulo', 'Compras')

@section('contenido')
<div class="mb-3">
    <a href="{{ route('compras.create') }}" class="btn btn-primario">Registrar compra</a>
</div>

<div class="tarjeta p-2 p-md-3">
    <div class="table-responsive">
        <table class="table tabla">
            <caption class="visually-hidden">Compras a proveedores, de la más reciente a la más antigua</caption>
            <thead><tr>
                <th scope="col">N°</th><th scope="col">Fecha</th><th scope="col">Proveedor</th>
                <th scope="col">Comprobante</th><th scope="col">Registró</th>
                <th scope="col" class="numero">Total (Bs)</th><th scope="col">Estado</th>
                <th scope="col"><span class="visually-hidden">Acciones</span></th>
            </tr></thead>
            <tbody>
            @forelse ($compras as $c)
                <tr>
                    <td>{{ $c->id }}</td>
                    <td>{{ $c->fecha->format('d/m/Y H:i') }}</td>
                    <td>{{ $c->proveedor->nombre }}</td>
                    <td>{{ $c->nro_comprobante }}</td>
                    <td>{{ $c->usuario->nombre }}</td>
                    <td class="numero">{{ number_format($c->total, 2, ',', '.') }}</td>
                    <td>
                        @if ($c->estado === \App\Models\Compra::ANULADA)
                            <span class="estado estado-neutro">Anulada</span>
                        @else
                            <span class="estado estado-exito">Registrada</span>
                        @endif
                    </td>
                    <td class="text-end">
                        @if ($c->estado === \App\Models\Compra::REGISTRADA)
                            <form method="POST" action="{{ route('compras.anular', $c) }}"
                                  onsubmit="return confirm('¿Anular la compra N° {{ $c->id }}? Se descontarán del inventario las unidades que ingresó.')">
                                @csrf
                                <button class="btn btn-sm btn-peligro">
                                    Anular<span class="visually-hidden"> la compra {{ $c->id }}</span>
                                </button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-center text-secundario py-4">Todavía no hay compras registradas.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $compras->links() }}</div>
@endsection
