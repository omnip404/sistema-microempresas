@extends('layouts.app')

@section('titulo', 'Bitácora')

@section('contenido')
<form class="tarjeta p-3 mb-3" method="GET" role="search" aria-label="Filtrar la bitácora">
    <div class="row g-2 align-items-end">
        <div class="col-md-3">
            <label class="form-label" for="usuario_id">Usuario</label>
            <select class="form-select" id="usuario_id" name="usuario_id">
                <option value="">Todos</option>
                @foreach ($usuarios as $u)
                    <option value="{{ $u->id }}" @selected(request('usuario_id') == $u->id)>{{ $u->nombre }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label" for="tabla">Tabla</label>
            <select class="form-select" id="tabla" name="tabla">
                <option value="">Todas</option>
                @foreach ($tablas as $t)
                    <option value="{{ $t }}" @selected(request('tabla') === $t)>{{ $t }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label" for="desde">Desde</label>
            <input class="form-control @error('desde') is-invalid @enderror" type="date" id="desde" name="desde" value="{{ request('desde') }}">
        </div>
        <div class="col-md-2">
            <label class="form-label" for="hasta">Hasta</label>
            <input class="form-control @error('hasta') is-invalid @enderror" type="date" id="hasta" name="hasta" value="{{ request('hasta') }}"
                   @error('hasta') aria-invalid="true" @enderror>
        </div>
        <div class="col-md-2 d-flex gap-2">
            <button class="btn btn-primario flex-grow-1">Filtrar</button>
            <a href="{{ route('bitacora.index') }}" class="btn btn-secundario">Limpiar</a>
        </div>
    </div>
</form>

<div class="tarjeta p-2 p-md-3">
    <div class="table-responsive">
        <table class="table tabla">
            <caption class="visually-hidden">Registro de acciones del sistema, de la más reciente a la más antigua</caption>
            <thead><tr>
                <th scope="col">Fecha</th><th scope="col">Usuario</th><th scope="col">Acción</th>
                <th scope="col">Tabla</th><th scope="col" class="numero">Registro</th>
                <th scope="col">Detalle</th><th scope="col">IP</th>
            </tr></thead>
            <tbody>
            @forelse ($registros as $r)
                <tr>
                    <td>{{ $r->created_at->format('d/m/Y H:i:s') }}</td>
                    <td>{{ $r->usuario->nombre ?? 'Sistema' }}</td>
                    <td>{{ $r->accion }}</td>
                    <td>{{ $r->tabla }}</td>
                    <td class="numero">{{ $r->registro_id }}</td>
                    <td>
                        @if ($r->detalle)
                            <dl class="mb-0 small">
                                @foreach ($r->detalle as $campo => $valor)
                                    <div class="d-flex gap-1">
                                        <dt class="fw-semibold">{{ $campo }}:</dt>
                                        <dd class="mb-0">{{ is_scalar($valor) || $valor === null ? ($valor ?? '—') : json_encode($valor, JSON_UNESCAPED_UNICODE) }}</dd>
                                    </div>
                                @endforeach
                            </dl>
                        @else
                            <span class="text-secundario">—</span>
                        @endif
                    </td>
                    <td>{{ $r->ip }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-secundario py-4">No hay registros con ese filtro.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $registros->links() }}</div>
@endsection
