<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Iniciar sesión · {{ config('app.name') }}</title>
    @include('partials.recursos')
</head>
<body class="d-flex align-items-center justify-content-center min-vh-100 p-3">
<main class="w-100" style="max-width: 420px">
    <div class="tarjeta p-4">
        <p class="marca mb-3">{{ config('app.name') }}<small>Gestión comercial</small></p>
        <h1 class="mb-1">Iniciar sesión</h1>
        <p class="text-secundario mb-4">Ingrese con el correo y la contraseña que le dio el administrador.</p>

        @error('email')
            <div class="mensaje mensaje-error mb-3" role="alert" id="error-acceso">
                <svg width="20" height="20" viewBox="0 0 20 20" aria-hidden="true"><circle cx="10" cy="10" r="8" fill="none" stroke="currentColor" stroke-width="2"/><path d="M10 5.5v5.5M10 13.5v1.5" stroke="currentColor" stroke-width="2.2"/></svg>
                <span>{{ $message }}</span>
            </div>
        @enderror

        <form method="POST" action="{{ url('/login') }}" novalidate>
            @csrf
            <div class="mb-3">
                <label class="form-label" for="email">Correo electrónico</label>
                <input class="form-control @error('email') is-invalid @enderror" type="email" id="email" name="email"
                       value="{{ old('email') }}" autocomplete="username" required autofocus
                       @error('email') aria-describedby="error-acceso" aria-invalid="true" @enderror>
            </div>
            <div class="mb-4">
                <label class="form-label" for="password">Contraseña</label>
                <div class="d-flex gap-2">
                    <input class="form-control" type="password" id="password" name="password" autocomplete="current-password" required>
                    <button class="btn btn-secundario" type="button" id="verClave" aria-pressed="false" aria-controls="password">Mostrar</button>
                </div>
            </div>
            <button class="btn btn-primario w-100">Ingresar</button>
        </form>
        <p class="form-text mt-3 mb-0">Por seguridad, tras 3 intentos fallidos el acceso se bloquea 5 minutos.</p>
    </div>

    <div class="mt-3">@include('partials.accesibilidad')</div>
</main>
<script>
    document.getElementById('verClave').addEventListener('click', function () {
        var campo = document.getElementById('password');
        var visible = campo.type === 'password';
        campo.type = visible ? 'text' : 'password';
        this.textContent = visible ? 'Ocultar' : 'Mostrar';
        this.setAttribute('aria-pressed', visible);
    });
</script>
</body>
</html>
