<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('codigo') · @yield('titulo') - {{ config('app.name') }}</title>
    @include('partials.recursos')
</head>
<body>
    <main class="container py-5" style="max-width: 40rem;">
        <div class="tarjeta p-4 p-md-5">
            <h1 class="h2 mb-3">@yield('codigo'): @yield('titulo')</h1>
            <p class="fs-5">@yield('mensaje')</p>
            @yield('detalle')
            <a href="{{ url('/') }}" class="btn btn-primario mt-3">Volver al inicio</a>
        </div>
    </main>
</body>
</html>
