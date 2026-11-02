<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('titulo', 'Inicio') · {{ config('app.name') }}</title>
    @include('partials.recursos')
    @stack('estilos')
</head>
<body>
<a class="saltar" href="#contenido">Saltar al contenido</a>

@php
    $esAdmin = auth()->user()->tieneRol('Administrador');
    $menu = array_filter([
        $esAdmin ? ['panel', 'Panel', 'panel'] : null,
        ['ventas.create', 'Registrar venta', 'ventas.create'],
        $esAdmin ? ['ventas.index', 'Ventas', 'ventas.index'] : null,
        ['clientes.index', 'Clientes', 'clientes.*'],
        $esAdmin ? ['productos.index', 'Productos', 'productos.*'] : null,
        $esAdmin ? ['categorias.index', 'Categorías', 'categorias.*'] : null,
        $esAdmin ? ['proveedores.index', 'Proveedores', 'proveedores.*'] : null,
        $esAdmin ? ['compras.index', 'Compras', 'compras.*'] : null,
        $esAdmin ? ['reportes.index', 'Reportes', 'reportes.*'] : null,
        $esAdmin ? ['comparacion.index', 'Comparar periodos', 'comparacion.*'] : null,
        $esAdmin ? ['usuarios.index', 'Usuarios', 'usuarios.*'] : null,
        $esAdmin ? ['bitacora.index', 'Bitácora', 'bitacora.*'] : null,
    ]);
@endphp

{{-- Celular y tablet: barra superior con botón de menú --}}
<header class="barra-movil d-lg-none d-flex align-items-center justify-content-between px-3 py-2">
    <span class="marca">{{ config('app.name') }}</span>
    <button class="btn btn-secundario" type="button" data-bs-toggle="offcanvas" data-bs-target="#menuLateral" aria-controls="menuLateral">
        <svg width="20" height="20" viewBox="0 0 20 20" aria-hidden="true"><path d="M3 5h14M3 10h14M3 15h14" stroke="currentColor" stroke-width="2"/></svg>
        Menú
    </button>
</header>

<div class="d-lg-flex">
    <nav class="lateral offcanvas-lg offcanvas-start" id="menuLateral" tabindex="-1" aria-label="Menú principal">
        <div class="offcanvas-header d-lg-none">
            <span class="marca">{{ config('app.name') }}</span>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#menuLateral" aria-label="Cerrar menú"></button>
        </div>
        <div class="offcanvas-body d-flex flex-column p-3 h-100">
            <div class="marca mb-4 d-none d-lg-block">{{ config('app.name') }}<small>Gestión comercial</small></div>

            <ul class="menu list-unstyled d-grid gap-1 mb-4">
                @foreach ($menu as [$ruta, $texto, $patron])
                    <li>
                        <a href="{{ route($ruta) }}" @if (request()->routeIs($patron)) aria-current="page" @endif>{{ $texto }}</a>
                    </li>
                @endforeach
            </ul>

            <div class="mt-auto">
                @include('partials.accesibilidad')
                <p class="small text-secundario mt-3 mb-2">
                    {{ auth()->user()->nombre }}<br>{{ auth()->user()->rol->nombre }}
                </p>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="btn btn-secundario w-100">Cerrar sesión</button>
                </form>
            </div>
        </div>
    </nav>

    <main id="contenido" tabindex="-1" class="flex-grow-1 p-3 p-lg-4" style="min-width:0">
        <h1 class="mb-4">@yield('titulo')</h1>

        @if (session('ok'))
            <div class="mensaje mensaje-exito mb-4" role="status">
                <svg width="20" height="20" viewBox="0 0 20 20" aria-hidden="true"><path d="M4 10.5l4 4 8-9" fill="none" stroke="currentColor" stroke-width="2.4"/></svg>
                <span>{{ session('ok') }}</span>
                @if (session('comprobante'))
                    <a class="ms-auto fw-semibold" href="{{ session('comprobante') }}" target="_blank" rel="noopener">Imprimir comprobante</a>
                @endif
            </div>
        @endif

        @if ($errors->any())
            <div class="mensaje mensaje-error mb-4" role="alert">
                <svg width="20" height="20" viewBox="0 0 20 20" aria-hidden="true"><circle cx="10" cy="10" r="8" fill="none" stroke="currentColor" stroke-width="2"/><path d="M10 5.5v5.5M10 13.5v1.5" stroke="currentColor" stroke-width="2.2"/></svg>
                <div>
                    <strong>Revise lo siguiente:</strong>
                    <ul class="mb-0 ps-3">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        @yield('contenido')
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
@stack('scripts')
</body>
</html>
