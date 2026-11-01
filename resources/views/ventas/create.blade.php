@extends('layouts.app')

@section('titulo', 'Registrar venta')

@section('contenido')
<form method="POST" action="{{ route('ventas.store') }}" id="formVenta">
    @csrf
    <div class="row g-3">
        <section class="col-lg-7" aria-labelledby="titulo-productos"><div class="tarjeta p-3 h-100">
            <h2 id="titulo-productos">Productos</h2>
            <div class="row g-2 align-items-end">
                <div class="col-sm-8">
                    <label class="form-label" for="buscador">Producto</label>
                    <select class="form-select" id="buscador">
                        @foreach ($productos as $p)
                            <option value="{{ $p->id }}" data-nombre="{{ $p->nombre }}" data-precio="{{ $p->precio_venta }}" data-stock="{{ $p->stock }}">
                                {{ $p->nombre }} · Bs {{ number_format($p->precio_venta, 2, ',', '.') }} · quedan {{ $p->stock }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-sm-2">
                    <label class="form-label" for="cantidad">Cantidad</label>
                    <input class="form-control" type="number" min="1" value="1" id="cantidad" inputmode="numeric">
                </div>
                <div class="col-6 col-sm-2">
                    <button type="button" class="btn btn-secundario w-100" id="agregar">Agregar</button>
                </div>
            </div>
            <p id="avisoStock" class="mensaje mensaje-aviso mt-3 mb-0 d-none" role="alert"></p>

            <div class="table-responsive mt-3">
                <table class="table tabla">
                    <caption class="visually-hidden">Productos agregados a esta venta</caption>
                    <thead><tr>
                        <th scope="col">Producto</th><th scope="col" class="numero">Cant.</th>
                        <th scope="col" class="numero">Precio</th><th scope="col" class="numero">Subtotal</th>
                        <th scope="col"><span class="visually-hidden">Quitar</span></th>
                    </tr></thead>
                    <tbody id="carrito"></tbody>
                </table>
            </div>
        </div></section>

        <section class="col-lg-5" aria-labelledby="titulo-resumen"><div class="tarjeta p-3">
            <h2 id="titulo-resumen">Resumen</h2>
            <label class="form-label" for="cliente_id">Cliente (opcional)</label>
            <select class="form-select mb-3" name="cliente_id" id="cliente_id">
                <option value="">Venta sin cliente registrado</option>
                @foreach ($clientes as $c)
                    <option value="{{ $c->id }}">{{ $c->nombre }}{{ $c->ci_nit ? ' · '.$c->ci_nit : '' }}</option>
                @endforeach
            </select>
            <div class="d-flex justify-content-between align-items-baseline border-top pt-3 mb-3">
                <span class="fs-5">Total</span>
                <strong class="kpi-valor" id="total" aria-live="polite">Bs 0,00</strong>
            </div>
            <button class="btn btn-primario w-100" id="confirmar" disabled>Confirmar venta</button>
            <p class="form-text mt-2 mb-0" id="ayudaConfirmar">Agregue al menos un producto para confirmar.</p>
        </div></section>
    </div>
</form>
@endsection

@push('scripts')
<script>
    (function () {
        var carrito = new Map();
        var tbody = document.getElementById('carrito');
        var aviso = document.getElementById('avisoStock');
        var bs = function (v) { return 'Bs ' + v.toLocaleString('es-BO', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); };
        var celda = function (texto, clase) { var td = document.createElement('td'); td.textContent = texto; if (clase) td.className = clase; return td; };

        function dibujar() {
            tbody.replaceChildren();
            var total = 0, i = 0;

            if (carrito.size === 0) {
                var vacio = document.createElement('tr');
                var td = celda('Todavía no agregó productos.', 'text-center text-secundario');
                td.colSpan = 5; vacio.appendChild(td); tbody.appendChild(vacio);
            }

            carrito.forEach(function (item, id) {
                var subtotal = item.cantidad * item.precio;
                total += subtotal;

                var fila = document.createElement('tr');
                var nombre = celda(item.nombre);
                [['producto_id', id], ['cantidad', item.cantidad]].forEach(function (par) {
                    var oculto = document.createElement('input');
                    oculto.type = 'hidden'; oculto.name = 'items[' + i + '][' + par[0] + ']'; oculto.value = par[1];
                    nombre.appendChild(oculto);
                });
                fila.append(nombre, celda(item.cantidad, 'numero'), celda(bs(item.precio), 'numero'), celda(bs(subtotal), 'numero'));

                var quitar = document.createElement('button');
                quitar.type = 'button'; quitar.className = 'btn btn-sm btn-peligro';
                quitar.textContent = 'Quitar';
                quitar.setAttribute('aria-label', 'Quitar ' + item.nombre);
                quitar.addEventListener('click', function () { carrito.delete(id); dibujar(); document.getElementById('buscador').focus(); });
                var acciones = celda(''); acciones.className = 'text-end'; acciones.appendChild(quitar);
                fila.appendChild(acciones);

                tbody.appendChild(fila);
                i++;
            });

            document.getElementById('total').textContent = bs(total);
            document.getElementById('confirmar').disabled = carrito.size === 0;
            document.getElementById('ayudaConfirmar').classList.toggle('d-none', carrito.size > 0);
        }

        function agregar() {
            var opcion = document.getElementById('buscador').selectedOptions[0];
            if (!opcion) return;
            var cantidad = parseInt(document.getElementById('cantidad').value, 10) || 1;
            var actual = carrito.has(opcion.value) ? carrito.get(opcion.value).cantidad : 0;
            var stock = parseInt(opcion.dataset.stock, 10);

            if (actual + cantidad > stock) {
                aviso.textContent = 'No alcanza el stock de ' + opcion.dataset.nombre + ': quedan ' + stock + ' unidades.';
                aviso.classList.remove('d-none');
                return;
            }
            aviso.classList.add('d-none');
            carrito.set(opcion.value, { nombre: opcion.dataset.nombre, precio: parseFloat(opcion.dataset.precio), cantidad: actual + cantidad });
            document.getElementById('cantidad').value = 1;
            dibujar();
        }

        document.getElementById('agregar').addEventListener('click', agregar);
        // Enter en la cantidad agrega el producto en vez de enviar la venta
        document.getElementById('cantidad').addEventListener('keydown', function (e) {
            if (e.key === 'Enter') { e.preventDefault(); agregar(); }
        });

        dibujar();
    })();
</script>
@endpush
