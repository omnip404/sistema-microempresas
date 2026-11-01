@extends('layouts.app')

@section('titulo', 'Productos')

@section('contenido')
<form class="tarjeta p-3 mb-3" method="GET" role="search" aria-label="Buscar productos">
    <div class="row g-2 align-items-end">
        <div class="col-md-5">
            <label class="form-label" for="buscar">Buscar</label>
            <input class="form-control" id="buscar" name="buscar" value="{{ request('buscar') }}" placeholder="Nombre o código">
        </div>
        <div class="col-md-4">
            <label class="form-label" for="categoria_id">Categoría</label>
            <select class="form-select" id="categoria_id" name="categoria_id">
                <option value="">Todas</option>
                @foreach ($categorias as $c)
                    <option value="{{ $c->id }}" @selected(request('categoria_id') == $c->id)>{{ $c->nombre }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3 d-flex gap-2">
            <button class="btn btn-secundario flex-grow-1">Filtrar</button>
            <a href="{{ route('productos.create') }}" class="btn btn-primario flex-grow-1">Nuevo producto</a>
        </div>
    </div>
</form>

<div class="tarjeta p-2 p-md-3">
    <div class="table-responsive">
        <table class="table tabla">
            <caption class="visually-hidden">Lista de productos con precio, stock y estado</caption>
            <thead><tr>
                <th scope="col">Código</th><th scope="col">Nombre</th><th scope="col">Categoría</th>
                <th scope="col" class="numero">Precio (Bs)</th><th scope="col" class="numero">Stock</th>
                <th scope="col">Estado</th><th scope="col"><span class="visually-hidden">Acciones</span></th>
            </tr></thead>
            <tbody>
            @forelse ($productos as $p)
                <tr>
                    <td>{{ $p->codigo }}</td>
                    <td>{{ $p->nombre }}</td>
                    <td>{{ $p->categoria->nombre }}</td>
                    <td class="numero">{{ number_format($p->precio_venta, 2, ',', '.') }}</td>
                    <td class="numero">{{ $p->stock }}</td>
                    <td>
                        @if (! $p->activo)
                            <span class="estado estado-neutro">De baja</span>
                        @elseif ($p->stock <= $p->stock_minimo)
                            <span class="estado estado-aviso">
                                <svg width="14" height="14" viewBox="0 0 14 14" aria-hidden="true"><path d="M7 1l6 11H1z" fill="none" stroke="currentColor" stroke-width="1.6"/><path d="M7 5v3.5M7 10v1" stroke="currentColor" stroke-width="1.6"/></svg>
                                Stock bajo
                            </span>
                        @else
                            <span class="estado estado-exito">Disponible</span>
                        @endif
                    </td>
                    <td class="text-end">
                        <a href="{{ route('productos.edit', $p) }}" class="btn btn-sm btn-secundario">
                            Editar<span class="visually-hidden"> {{ $p->nombre }}</span>
                        </a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-secundario py-4">No se encontraron productos con ese filtro.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $productos->links() }}</div>
@endsection
