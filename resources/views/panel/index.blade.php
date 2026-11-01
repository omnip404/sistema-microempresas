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
        <p class="kpi-etiqueta mb-1">Venta promedio del mes</p>
        <p class="kpi-valor mb-0">Bs {{ number_format($ticketPromedio, 2, ',', '.') }}</p>
    </div></div>
    <div class="col-6 col-xl-3"><div class="tarjeta p-3 h-100">
        <p class="kpi-etiqueta mb-1">Productos con stock bajo</p>
        <p class="kpi-valor mb-0">{{ $stockBajo->count() }}</p>
    </div></div>
</section>

<div class="row g-3">
    <section class="col-lg-8" aria-labelledby="titulo-grafico"><div class="tarjeta p-3 h-100">
        <h2 id="titulo-grafico">Ventas de los últimos 7 días, en Bs</h2>
        <canvas id="graficoVentas" height="130" role="img"
                aria-label="Gráfico de barras de ventas diarias. Los datos están en la tabla que sigue."></canvas>
        <details class="mt-2">
            <summary>Ver los datos en tabla</summary>
            <table class="table tabla mt-2">
                <thead><tr><th scope="col">Día</th><th scope="col" class="numero">Ventas (Bs)</th></tr></thead>
                <tbody>
                @foreach ($grafico['etiquetas'] as $i => $dia)
                    <tr><td>{{ $dia }}</td><td class="numero">{{ number_format($grafico['valores'][$i], 2, ',', '.') }}</td></tr>
                @endforeach
                </tbody>
            </table>
        </details>
    </div></section>

    <div class="col-lg-4 d-grid gap-3 align-content-start">
        <section class="tarjeta p-3" aria-labelledby="titulo-alertas">
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

        <section class="tarjeta p-3" aria-labelledby="titulo-vendidos">
            <h2 id="titulo-vendidos">Más vendidos</h2>
            <ol class="mb-0 ps-3">
                @forelse ($masVendidos as $p)
                    <li class="py-1">{{ $p->nombre }} <span class="text-secundario">· {{ $p->unidades }} unid.</span></li>
                @empty
                    <li class="text-secundario">Todavía no hay ventas registradas.</li>
                @endforelse
            </ol>
        </section>
    </div>
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
            type: 'bar',
            data: {
                labels: @json($grafico['etiquetas']),
                datasets: [{ data: @json($grafico['valores']), backgroundColor: color('--grafico'), borderRadius: 4, maxBarThickness: 48 }]
            },
            options: {
                plugins: {
                    legend: { display: false },
                    tooltip: { callbacks: { label: function (c) { return bs(c.parsed.y); } } }
                },
                scales: {
                    x: { grid: { display: false } },
                    y: { beginAtZero: true, grid: { color: color('--borde') }, border: { display: false },
                         ticks: { maxTicksLimit: 5, callback: function (v) { return 'Bs ' + v; } } }
                }
            }
        });
    })();
</script>
@endpush
