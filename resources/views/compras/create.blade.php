@extends('layouts.app')

@section('titulo', 'Registrar compra')

@section('contenido')
<form method="POST" action="{{ route('compras.store') }}" id="formCompra" novalidate>
    @csrf
    <div class="row g-3">
        <section class="col-lg-8" aria-labelledby="titulo-productos"><div class="tarjeta p-3 h-100">
            <h2 id="titulo-productos">Productos comprados</h2>
            <div class="row g-2 align-items-end">
                <div class="col-sm-5">
                    <label class="form-label" for="buscador">Producto</label>
                    <select class="form-select" id="buscador">
                        @foreach ($productos as $p)
                            <option value="{{ $p->id }}" data-nombre="{{ $p->nombre }}" data-costo="{{ $p->precio_compra }}">
                                {{ $p->nombre }} · stock {{ $p->stock }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-sm-2">
                    <label class="form-label" for="cantidad">Cantidad</label>
                    <input class="form-control" type="number" min="1" value="1" id="cantidad" inputmode="numeric">
                </div>
                <div class="col-6 col-sm-3">
                    <label class="form-label" for="costo">Costo unitario (Bs)</label>
                    <input class="form-control" type="number" min="0" step="0.01" id="costo" inputmode="decimal">
                </div>
                <div class="col-sm-2">
                    <button type="button" class="btn btn-secundario w-100" id="agregar">Agregar</button>
                </div>
            </div>
            <p id="avisoCarrito" class="mensaje mensaje-aviso mt-3 mb-0 d-none" role="alert"></p>

            <div class="table-responsive mt-3">
                <table class="table tabla">
                    <caption class="visually-hidden">Productos agregados a esta compra; cantidad y costo se pueden editar</caption>
                    <thead><tr>
                        <th scope="col">Producto</th><th scope="col" class="numero">Cantidad</th>
                        <th scope="col" class="numero">Costo unitario (Bs)</th><th scope="col" class="numero">Subtotal<span class="visually-hidden"> (cantidad por costo unitario)</span></th>
                        <th scope="col"><span class="visually-hidden">Quitar</span></th>
                    </tr></thead>
                    <tbody id="carrito"></tbody>
                </table>
            </div>
        </div></section>

        <section class="col-lg-4" aria-labelledby="titulo-resumen"><div class="tarjeta p-3">
            <h2 id="titulo-resumen">Resumen</h2>

            <label class="form-label" for="proveedor_id">Proveedor</label>
            <select class="form-select mb-3 @error('proveedor_id') is-invalid @enderror" name="proveedor_id" id="proveedor_id" required
                    @error('proveedor_id') aria-invalid="true" aria-describedby="error-proveedor_id" @enderror>
                <option value="">Seleccione un proveedor</option>
                @foreach ($proveedores as $pr)
                    <option value="{{ $pr->id }}" @selected(old('proveedor_id') == $pr->id)>{{ $pr->nombre }}</option>
                @endforeach
            </select>
            @error('proveedor_id') <div class="invalid-feedback d-block mb-3" id="error-proveedor_id">{{ $message }}</div> @enderror

            <label class="form-label" for="nro_comprobante">N.º de comprobante (opcional)</label>
            <input class="form-control mb-3 @error('nro_comprobante') is-invalid @enderror" id="nro_comprobante" name="nro_comprobante"
                   maxlength="30" value="{{ old('nro_comprobante') }}"
                   @error('nro_comprobante') aria-invalid="true" aria-describedby="error-nro_comprobante" @enderror>
            @error('nro_comprobante') <div class="invalid-feedback d-block mb-3" id="error-nro_comprobante">{{ $message }}</div> @enderror

            <div class="d-flex justify-content-between align-items-baseline border-top pt-3 mb-3">
                <span class="fs-5">Total</span>
                <strong class="kpi-valor" id="total" aria-live="polite">Bs 0,00</strong>
            </div>
            <button class="btn btn-primario w-100" id="confirmar" disabled>Confirmar compra</button>
            <p class="form-text mt-2 mb-0" id="ayudaConfirmar">Agregue al menos un producto para confirmar.</p>
            <a href="{{ route('compras.index') }}" class="btn btn-secundario w-100 mt-2">Cancelar</a>
        </div></section>
    </div>
</form>
@endsection

@push('scripts')
<script>
    (function () {
        var carrito = new Map();
        var tbody = document.getElementById('carrito');
        var aviso = document.getElementById('avisoCarrito');
        var selector = document.getElementById('buscador');
        var bs = function (v) { return 'Bs ' + v.toLocaleString('es-BO', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); };
        var celda = function (texto, clase) { var td = document.createElement('td'); td.textContent = texto; if (clase) td.className = clase; return td; };

        function sugerirCosto() {
            var opcion = selector.selectedOptions[0];
            if (opcion) document.getElementById('costo').value = parseFloat(opcion.dataset.costo).toFixed(2);
        }

        function entrada(nombre, etiqueta, valor, min, paso, alCambiar) {
            var input = document.createElement('input');
            input.type = 'number'; input.className = 'form-control text-end'; input.name = nombre;
            input.min = min; input.step = paso; input.value = valor; input.required = true;
            input.setAttribute('aria-label', etiqueta);
            input.style.minWidth = '90px';
            input.addEventListener('input', alCambiar);
            return input;
        }

        function totalActual() {
            var total = 0;
            carrito.forEach(function (item) { total += item.cantidad * item.costo; });
            return total;
        }

        function actualizarTotal() {
            document.getElementById('total').textContent = bs(totalActual());
            carrito.forEach(function (item) { item.celdaSubtotal.textContent = bs(item.cantidad * item.costo); });
        }

        function dibujar() {
            tbody.replaceChildren();
            var i = 0;

            if (carrito.size === 0) {
                var vacio = document.createElement('tr');
                var td = celda('Todavía no agregó productos.', 'text-center text-secundario');
                td.colSpan = 5; vacio.appendChild(td); tbody.appendChild(vacio);
            }

            carrito.forEach(function (item, id) {
                var fila = document.createElement('tr');
                var nombre = celda(item.nombre);
                var oculto = document.createElement('input');
                oculto.type = 'hidden'; oculto.name = 'items[' + i + '][producto_id]'; oculto.value = id;
                nombre.appendChild(oculto);

                var tdCantidad = celda(''); tdCantidad.className = 'numero';
                tdCantidad.appendChild(entrada('items[' + i + '][cantidad]', 'Cantidad de ' + item.nombre, item.cantidad, 1, 1, function (e) {
                    item.cantidad = parseInt(e.target.value, 10) || 0; actualizarTotal();
                }));
                var tdCosto = celda(''); tdCosto.className = 'numero';
                tdCosto.appendChild(entrada('items[' + i + '][costo_unitario]', 'Costo unitario de ' + item.nombre, item.costo, 0, '0.01', function (e) {
                    item.costo = parseFloat(e.target.value) || 0; actualizarTotal();
                }));
                item.celdaSubtotal = celda('', 'numero');

                var quitar = document.createElement('button');
                quitar.type = 'button'; quitar.className = 'btn btn-sm btn-peligro';
                quitar.textContent = 'Quitar';
                quitar.setAttribute('aria-label', 'Quitar ' + item.nombre);
                quitar.addEventListener('click', function () { carrito.delete(id); dibujar(); selector.focus(); });
                var acciones = celda(''); acciones.className = 'text-end'; acciones.appendChild(quitar);

                fila.append(nombre, tdCantidad, tdCosto, item.celdaSubtotal, acciones);
                tbody.appendChild(fila);
                i++;
            });

            actualizarTotal();
            document.getElementById('confirmar').disabled = carrito.size === 0;
            document.getElementById('ayudaConfirmar').classList.toggle('d-none', carrito.size > 0);
        }

        function agregar() {
            var opcion = selector.selectedOptions[0];
            if (!opcion) return;
            var cantidad = parseInt(document.getElementById('cantidad').value, 10);
            var costo = parseFloat(document.getElementById('costo').value);

            if (!(cantidad >= 1) || !(costo >= 0)) {
                aviso.textContent = 'Indique una cantidad de 1 o más y un costo unitario válido.';
                aviso.classList.remove('d-none');
                return;
            }
            aviso.classList.add('d-none');

            var existente = carrito.get(opcion.value);
            if (existente) { existente.cantidad += cantidad; existente.costo = costo; }
            else carrito.set(opcion.value, { nombre: opcion.dataset.nombre, cantidad: cantidad, costo: costo });

            document.getElementById('cantidad').value = 1;
            dibujar();
        }

        selector.addEventListener('change', sugerirCosto);
        document.getElementById('agregar').addEventListener('click', agregar);
        // Enter en cantidad o costo agrega el producto en vez de enviar la compra
        ['cantidad', 'costo'].forEach(function (id) {
            document.getElementById(id).addEventListener('keydown', function (e) {
                if (e.key === 'Enter') { e.preventDefault(); agregar(); }
            });
        });

        sugerirCosto();
        dibujar();
    })();
</script>
@endpush
