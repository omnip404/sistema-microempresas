@extends('layouts.app')

@section('titulo', 'Proveedores')

@section('contenido')
<form class="tarjeta p-3 mb-3" method="GET" role="search" aria-label="Buscar proveedores">
    <div class="row g-2 align-items-end">
        <div class="col-md-9">
            <label class="form-label" for="buscar">Buscar</label>
            <input class="form-control" id="buscar" name="buscar" value="{{ request('buscar') }}" placeholder="Nombre o NIT">
        </div>
        <div class="col-md-3 d-flex gap-2">
            <button class="btn btn-secundario flex-grow-1">Filtrar</button>
            <a href="{{ route('proveedores.create') }}" class="btn btn-primario flex-grow-1">Nuevo proveedor</a>
        </div>
    </div>
</form>

<div class="tarjeta p-2 p-md-3">
    <div class="table-responsive">
        <table class="table tabla">
            <caption class="visually-hidden">Lista de proveedores con NIT, teléfono y estado</caption>
            <thead><tr>
                <th scope="col">Nombre</th><th scope="col">NIT</th><th scope="col">Teléfono</th>
                <th scope="col">Dirección</th><th scope="col">Estado</th>
                <th scope="col"><span class="visually-hidden">Acciones</span></th>
            </tr></thead>
            <tbody>
            @forelse ($proveedores as $p)
                <tr>
                    <td>{{ $p->nombre }}</td>
                    <td>{{ $p->nit }}</td>
                    <td>{{ $p->telefono }}</td>
                    <td>{{ $p->direccion }}</td>
                    <td>
                        @if ($p->activo)
                            <span class="estado estado-exito">Activo</span>
                        @else
                            <span class="estado estado-neutro">De baja</span>
                        @endif
                    </td>
                    <td class="text-end">
                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('proveedores.edit', $p) }}" class="btn btn-sm btn-secundario">
                                Editar<span class="visually-hidden"> {{ $p->nombre }}</span>
                            </a>
                            @if ($p->activo)
                                <form method="POST" action="{{ route('proveedores.destroy', $p) }}"
                                      onsubmit="return confirm(@js('¿Dar de baja al proveedor '.$p->nombre.'?'))">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-peligro">
                                        Dar de baja<span class="visually-hidden"> {{ $p->nombre }}</span>
                                    </button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-secundario py-4">No se encontraron proveedores con ese filtro.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $proveedores->links() }}</div>
@endsection
