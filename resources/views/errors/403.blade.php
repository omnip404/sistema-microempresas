@extends('errors.plantilla')

@section('codigo', '403')
@section('titulo', 'Acceso denegado')
@section('mensaje', 'No tiene permiso para ver esta página.')
@if (trim((string) ($exception->getMessage() ?? '')) !== '')
    @section('detalle')
        <p class="fw-semibold">{{ $exception->getMessage() }}</p>
    @endsection
@endif
