@extends('layouts.app')

@section('titulo', 'Categorías')

@section('contenido')
<div class="mb-3">
    <a href="{{ route('categorias.create') }}" class="btn btn-primario">Nueva categoría</a>
</div>

<div class="tarjeta p-2 p-md-3">
    <div class="table-responsive">
        <table class="table tabla">
            <caption class="visually-hidden">Lista de categorías con la cantidad de productos de cada una</caption>
            <thead><tr>
                <th scope="col">Nombre</th><th scope="col">Descripción</th>
                <th scope="col" class="numero">Productos</th>
                <th scope="col"><span class="visually-hidden">Acciones</span></th>
            </tr></thead>
            <tbody>
            @forelse ($categorias as $c)
                <tr>
                    <td>{{ $c->nombre }}</td>
                    <td>{{ $c->descripcion }}</td>
                    <td class="numero">{{ $c->productos_count }}</td>
                    <td class="text-end">
                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('categorias.edit', $c) }}" class="btn btn-sm btn-secundario">
                                Editar<span class="visually-hidden"> {{ $c->nombre }}</span>
                            </a>
                            <form method="POST" action="{{ route('categorias.destroy', $c) }}"
                                  onsubmit="return confirm(@js('¿Eliminar la categoría '.$c->nombre.'?'))">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-peligro">
                                    Eliminar<span class="visually-hidden"> {{ $c->nombre }}</span>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-center text-secundario py-4">Aún no hay categorías registradas.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $categorias->links() }}</div>
@endsection
