@extends('layouts.app')

@section('titulo', 'Clientes')

@section('contenido')
<form class="tarjeta p-3 mb-3" method="GET" role="search" aria-label="Buscar clientes">
    <div class="row g-2 align-items-end">
        <div class="col-md-9">
            <label class="form-label" for="buscar">Buscar</label>
            <input class="form-control" id="buscar" name="buscar" value="{{ request('buscar') }}" placeholder="Nombre o CI/NIT">
        </div>
        <div class="col-md-3 d-flex gap-2">
            <button class="btn btn-secundario flex-grow-1">Filtrar</button>
            <a href="{{ route('clientes.create') }}" class="btn btn-primario flex-grow-1">Nuevo cliente</a>
        </div>
    </div>
</form>

<div class="tarjeta p-2 p-md-3">
    <div class="table-responsive">
        <table class="table tabla">
            <caption class="visually-hidden">Lista de clientes con CI o NIT y teléfono</caption>
            <thead><tr>
                <th scope="col">Nombre</th><th scope="col">CI/NIT</th><th scope="col">Teléfono</th>
                <th scope="col"><span class="visually-hidden">Acciones</span></th>
            </tr></thead>
            <tbody>
            @forelse ($clientes as $c)
                <tr>
                    <td>{{ $c->nombre }}</td>
                    <td>{{ $c->ci_nit }}</td>
                    <td>{{ $c->telefono }}</td>
                    <td class="text-end">
                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('clientes.edit', $c) }}" class="btn btn-sm btn-secundario">
                                Editar<span class="visually-hidden"> {{ $c->nombre }}</span>
                            </a>
                            @if (auth()->user()->tieneRol(\App\Models\Rol::ADMINISTRADOR))
                                <form method="POST" action="{{ route('clientes.destroy', $c) }}"
                                      onsubmit="return confirm(@js('¿Eliminar al cliente '.$c->nombre.'?'))">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-peligro">
                                        Eliminar<span class="visually-hidden"> {{ $c->nombre }}</span>
                                    </button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-center text-secundario py-4">No se encontraron clientes con ese filtro.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $clientes->links() }}</div>
@endsection
