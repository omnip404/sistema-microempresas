{{-- Aplica las preferencias de accesibilidad antes de dibujar la página (evita un parpadeo) --}}
<script>
    (function () {
        var html = document.documentElement;
        html.dataset.letra = localStorage.getItem('letra') || '0';
        html.dataset.contraste = localStorage.getItem('contraste') || 'normal';
    })();
</script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Atkinson+Hyperlegible:wght@400;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="{{ asset('css/app.css') }}" rel="stylesheet">
