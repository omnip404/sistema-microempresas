@extends('layouts.app')

@section('titulo', 'Movimientos de '.$producto->nombre)

@section('contenido')
<div class="d-flex flex-wrap gap-2 align-items-center mb-3">
    <p class="mb-0 me-auto">Stock actual: <strong class="numero">{{ $producto->stock }}</strong> {{ $producto->unidad_medida }}</p>
    <a href="{{ route('inventario.ajuste', $producto) }}" class="btn btn-primario">Registrar ajuste</a>
    <a href="{{ route('productos.index') }}" class="btn btn-secundario">Volver a productos</a>
</div>

<div class="tarjeta p-2 p-md-3">
    <div class="table-responsive">
        <table class="table tabla">
            <caption class="visually-hidden">Historial de movimientos de inventario de {{ $producto->nombre }}, del más reciente al más antiguo</caption>
            <thead><tr>
                <th scope="col">Fecha</th><th scope="col">Tipo</th>
                <th scope="col" class="numero">Cantidad</th><th scope="col" class="numero">Stock resultante</th>
                <th scope="col">Referencia</th><th scope="col">Usuario</th><th scope="col">Observación</th>
            </tr></thead>
            <tbody>
            @forelse ($movimientos as $m)
                <tr>
                    <td>{{ $m->created_at->format('d/m/Y H:i') }}</td>
                    <td>
                        <span class="estado {{ $m->tipo === 'ENTRADA' ? 'estado-exito' : ($m->tipo === 'SALIDA' ? 'estado-neutro' : 'estado-aviso') }}">
                            {{ ucfirst(strtolower($m->tipo)) }}
                        </span>
                    </td>
                    <td class="numero">{{ $m->cantidad > 0 ? '+' : '' }}{{ $m->cantidad }}</td>
                    <td class="numero">{{ $m->stock_resultante }}</td>
                    <td>{{ $m->referencia }}</td>
                    <td>{{ $m->usuario->nombre }}</td>
                    <td>{{ $m->observacion }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-secundario py-4">Este producto todavía no tiene movimientos.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $movimientos->links() }}</div>
@endsection
