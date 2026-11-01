{{-- Controles para personas con baja visión: tamaño de letra y alto contraste --}}
<div role="group" aria-label="Opciones de lectura">
    <p class="small text-secundario mb-1">Opciones de lectura</p>
    <div class="accesibilidad">
        <button type="button" data-cambiar-letra="-1" aria-label="Reducir el tamaño de letra">A−</button>
        <button type="button" data-cambiar-letra="1" aria-label="Aumentar el tamaño de letra">A+</button>
        <button type="button" id="btnContraste" aria-pressed="false">Alto contraste</button>
    </div>
</div>
<script>
    (function () {
        var html = document.documentElement;
        var botonContraste = document.getElementById('btnContraste');
        botonContraste.setAttribute('aria-pressed', html.dataset.contraste === 'alto');

        document.querySelectorAll('[data-cambiar-letra]').forEach(function (boton) {
            boton.addEventListener('click', function () {
                var nivel = Math.min(2, Math.max(0, Number(html.dataset.letra) + Number(boton.dataset.cambiarLetra)));
                html.dataset.letra = nivel;
                localStorage.setItem('letra', nivel);
            });
        });

        botonContraste.addEventListener('click', function () {
            var alto = html.dataset.contraste !== 'alto';
            html.dataset.contraste = alto ? 'alto' : 'normal';
            localStorage.setItem('contraste', html.dataset.contraste);
            botonContraste.setAttribute('aria-pressed', alto);
        });
    })();
</script>
