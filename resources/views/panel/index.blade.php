@extends('layouts.app')

@section('titulo', 'Panel')

@section('contenido')
<section aria-label="Indicadores principales" class="row g-3 mb-4">
    <div class="col-6 col-xl-3"><div class="tarjeta p-3 h-100">
        <p class="kpi-etiqueta mb-1">Ventas de hoy</p>
        <p class="kpi-valor mb-0">Bs {{ number_format($ventasHoy, 2, ',', '.') }}</p>
    </div></div>
    <div class="col-6 col-xl-3"><div class="tarjeta p-3 h-100">
        <p class="kpi-etiqueta mb-1">Ventas del mes</p>
        <p class="kpi-valor mb-0">Bs {{ number_format($ventasMes, 2, ',', '.') }}</p>
    </div></div>
    <div class="col-6 col-xl-3"><div class="tarjeta p-3 h-100">
        <p class="kpi-etiqueta mb-1">Ganancia estimada del mes</p>
        <p class="kpi-valor mb-0">Bs {{ number_format($gananciaMes, 2, ',', '.') }}</p>
        <p class="small text-secundario mb-0">Estimada: ventas menos el precio de compra actual de cada producto.</p>
    </div></div>
    <div class="col-6 col-xl-3"><div class="tarjeta p-3 h-100">
        <p class="kpi-etiqueta mb-1">Productos con stock bajo</p>
        <p class="kpi-valor mb-0">{{ $stockBajo->count() }}</p>
    </div></div>
</section>

<form class="tarjeta p-3 mb-3" method="GET" action="{{ route('panel') }}" aria-label="Filtrar los gráficos por fechas">
    <div class="row g-2 align-items-end">
        <div class="col-6 col-md-3">
            <label class="form-label" for="desde">Desde</label>
            <input class="form-control @error('desde') is-invalid @enderror" type="date" id="desde" name="desde" value="{{ $desde->toDateString() }}" required
                   @error('desde') aria-invalid="true" aria-describedby="error-desde" @enderror>
            @error('desde') <div class="invalid-feedback d-block" id="error-desde">{{ $message }}</div> @enderror
        </div>
        <div class="col-6 col-md-3">
            <label class="form-label" for="hasta">Hasta</label>
            <input class="form-control @error('hasta') is-invalid @enderror" type="date" id="hasta" name="hasta" value="{{ $hasta->toDateString() }}" required
                   @error('hasta') aria-invalid="true" aria-describedby="error-hasta" @enderror>
            @error('hasta') <div class="invalid-feedback d-block" id="error-hasta">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-6 d-flex flex-wrap gap-2">
            <button class="btn btn-primario">Filtrar gráficos</button>
            <a class="btn btn-secundario" href="{{ route('panel') }}">Mes actual</a>
        </div>
    </div>
    <p class="form-text mb-0 mt-2">Los gráficos de ventas por día y de productos más vendidos usan este periodo; los indicadores de arriba son del mes actual.</p>
</form>

<div class="row g-3">
    <section class="col-lg-8" aria-labelledby="titulo-grafico"><div class="tarjeta p-3 h-100">
        <h2 id="titulo-grafico">Ventas por día del {{ $desde->format('d/m/Y') }} al {{ $hasta->format('d/m/Y') }}, en Bs</h2>
        <canvas id="graficoVentas" height="130" role="img"
                aria-label="Gráfico de líneas de ventas por día. Los datos están en la tabla que sigue."></canvas>
        <details class="mt-2">
            <summary>Ver los datos en tabla</summary>
            <table class="table tabla mt-2">
                <caption class="visually-hidden">Ventas por día del periodo</caption>
                <thead><tr><th scope="col">Día</th><th scope="col" class="numero">Ventas (Bs)</th></tr></thead>
                <tbody>
                @foreach ($lineas['etiquetas'] as $i => $dia)
                    <tr><td>{{ $dia }}</td><td class="numero">{{ number_format($lineas['valores'][$i], 2, ',', '.') }}</td></tr>
                @endforeach
                </tbody>
                <tfoot><tr class="fw-bold"><th scope="row">Total</th><td class="numero">{{ number_format($lineas['total'], 2, ',', '.') }}</td></tr></tfoot>
            </table>
        </details>
    </div></section>

    <section class="col-lg-4 tarjeta p-3" aria-labelledby="titulo-alertas">
        <h2 id="titulo-alertas">Stock bajo</h2>
        @forelse ($stockBajo as $p)
            <div class="d-flex justify-content-between align-items-center gap-2 py-2 border-bottom">
                <span>{{ $p->nombre }}</span>
                <span class="estado estado-aviso">
                    <svg width="14" height="14" viewBox="0 0 14 14" aria-hidden="true"><path d="M7 1l6 11H1z" fill="none" stroke="currentColor" stroke-width="1.6"/><path d="M7 5v3.5M7 10v1" stroke="currentColor" stroke-width="1.6"/></svg>
                    Quedan {{ $p->stock }} (mín. {{ $p->stock_minimo }})
                </span>
            </div>
        @empty
            <p class="text-secundario mb-0">Todos los productos están sobre su stock mínimo.</p>
        @endforelse
    </section>

    <section class="col-12" aria-labelledby="titulo-vendidos"><div class="tarjeta p-3">
        <h2 id="titulo-vendidos">Los {{ \App\Services\ReporteService::LIMITE_MAS_VENDIDOS }} productos más vendidos del periodo, en unidades</h2>
        @if (count($masVendidos) > 0)
            <canvas id="graficoVendidos" height="{{ 40 + 28 * count($masVendidos) }}" role="img"
                    aria-label="Gráfico de barras de los productos más vendidos. Los datos están en la tabla que sigue."></canvas>
            <details class="mt-2">
                <summary>Ver los datos en tabla</summary>
                <table class="table tabla mt-2">
                    <caption class="visually-hidden">Productos más vendidos del periodo</caption>
                    <thead><tr><th scope="col">Producto</th><th scope="col" class="numero">Unidades</th></tr></thead>
                    <tbody>
                    @foreach ($masVendidos as $p)
                        <tr><td>{{ $p['nombre'] }}</td><td class="numero">{{ $p['unidades'] }}</td></tr>
                    @endforeach
                    </tbody>
                </table>
            </details>
        @else
            <p class="text-secundario mb-0">No hay ventas registradas en este periodo.</p>
        @endif
    </div></section>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<script>
    (function () {
        var estilo = getComputedStyle(document.documentElement);
        var color = function (nombre) { return estilo.getPropertyValue(nombre).trim(); };
        var bs = function (v) { return 'Bs ' + v.toLocaleString('es-BO', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); };

        Chart.defaults.font.family = getComputedStyle(document.body).fontFamily;
        Chart.defaults.font.size = 14;
        Chart.defaults.color = color('--texto-2');

        new Chart(document.getElementById('graficoVentas'), {
            type: 'line',
            data: {
                labels: @json($lineas['etiquetas']),
                datasets: [{ data: @json($lineas['valores']), borderColor: color('--grafico'), backgroundColor: color('--grafico'),
                             borderWidth: 2, pointRadius: 3, tension: 0.2 }]
            },
            options: {
                plugins: {
                    legend: { display: false },
                    tooltip: { callbacks: { label: function (c) { return bs(c.parsed.y); } } }
                },
                scales: {
                    x: { grid: { display: false }, ticks: { maxTicksLimit: 12 } },
                    y: { beginAtZero: true, grid: { color: color('--borde') }, border: { display: false },
                         ticks: { maxTicksLimit: 5, callback: function (v) { return 'Bs ' + v; } } }
                }
            }
        });

        var lienzoVendidos = document.getElementById('graficoVendidos');
        if (lienzoVendidos) {
            new Chart(lienzoVendidos, {
                type: 'bar',
                data: {
                    labels: @json(collect($masVendidos)->pluck('nombre')),
                    datasets: [{ data: @json(collect($masVendidos)->pluck('unidades')), backgroundColor: color('--grafico'), borderRadius: 4, maxBarThickness: 28 }]
                },
                options: {
                    indexAxis: 'y',
                    plugins: { legend: { display: false } },
                    scales: {
                        x: { beginAtZero: true, grid: { color: color('--borde') }, border: { display: false }, ticks: { precision: 0 } },
                        y: { grid: { display: false } }
                    }
                }
            });
        }
    })();
</script>
@endpush
