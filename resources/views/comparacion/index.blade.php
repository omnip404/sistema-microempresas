@extends('layouts.app')

@section('titulo', 'Comparar periodos')

@php
    $a = $comparacion['a'];
    $b = $comparacion['b'];
    $variacion = $comparacion['variacion'];
    $etiquetaA = $desdeA->format('d/m/Y').' al '.$hastaA->format('d/m/Y');
    $etiquetaB = $desdeB->format('d/m/Y').' al '.$hastaB->format('d/m/Y');

    $metricas = [
        ['total', 'Total vendido (Bs)', fn ($v) => number_format($v, 2, ',', '.')],
        ['ventas', 'N.º de ventas', fn ($v) => number_format($v, 0, ',', '.')],
        ['promedio', 'Venta promedio (Bs)', fn ($v) => number_format($v, 2, ',', '.')],
    ];

    $campos = [
        ['a', 'Periodo A (el que se evalúa)', $desdeA, $hastaA],
        ['b', 'Periodo B (contra el que se compara)', $desdeB, $hastaB],
    ];
@endphp

@section('contenido')
<form class="tarjeta p-3 mb-3" method="GET" action="{{ route('comparacion.index') }}" aria-label="Elegir los periodos a comparar">
    <div class="row g-3 align-items-end">
        @foreach ($campos as [$letra, $titulo, $desde, $hasta])
            <fieldset class="col-md-5">
                <legend class="form-label fw-semibold">{{ $titulo }}</legend>
                <div class="row g-2">
                    @foreach (['desde' => $desde, 'hasta' => $hasta] as $lado => $fecha)
                        @php $nombre = "{$lado}_{$letra}"; @endphp
                        <div class="col-6">
                            <label class="form-label" for="{{ $nombre }}">{{ ucfirst($lado) }}</label>
                            <input class="form-control @error($nombre) is-invalid @enderror" type="date" id="{{ $nombre }}" name="{{ $nombre }}"
                                   value="{{ $fecha->toDateString() }}" required @error($nombre) aria-invalid="true" aria-describedby="error-{{ $nombre }}" @enderror>
                            @error($nombre) <div class="invalid-feedback d-block" id="error-{{ $nombre }}">{{ $message }}</div> @enderror
                        </div>
                    @endforeach
                </div>
            </fieldset>
        @endforeach
        <div class="col-md-2"><button class="btn btn-primario w-100">Comparar</button></div>
    </div>
</form>

<div class="row g-3">
    <section class="col-lg-7" aria-labelledby="titulo-tabla"><div class="tarjeta p-2 p-md-3 h-100">
        <h2 id="titulo-tabla" class="h4 px-2">Resultado</h2>
        <div class="table-responsive">
            <table class="table tabla">
                <caption class="visually-hidden">Comparación de ventas entre el periodo A y el periodo B, con su variación porcentual</caption>
                <thead><tr>
                    <th scope="col">Indicador</th>
                    <th scope="col" class="numero">Periodo A<br><small class="fw-normal">{{ $etiquetaA }}</small></th>
                    <th scope="col" class="numero">Periodo B<br><small class="fw-normal">{{ $etiquetaB }}</small></th>
                    <th scope="col" class="numero">Variación</th>
                </tr></thead>
                <tbody>
                @foreach ($metricas as [$clave, $nombre, $formato])
                    <tr>
                        <th scope="row" class="fw-normal">{{ $nombre }}</th>
                        <td class="numero">{{ $formato($a[$clave]) }}</td>
                        <td class="numero">{{ $formato($b[$clave]) }}</td>
                        <td class="numero">
                            @if ($variacion[$clave] === null)
                                <span class="text-secundario">sin datos del periodo anterior</span>
                            @elseif ($variacion[$clave] > 0)
                                <span class="estado estado-exito">Subió {{ number_format($variacion[$clave], 1, ',', '.') }} %</span>
                            @elseif ($variacion[$clave] < 0)
                                <span class="estado estado-aviso">Bajó {{ number_format(abs($variacion[$clave]), 1, ',', '.') }} %</span>
                            @else
                                <span class="estado estado-neutro">Sin cambio (0,0 %)</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div></section>

    <section class="col-lg-5" aria-labelledby="titulo-grafico"><div class="tarjeta p-3 h-100">
        <h2 id="titulo-grafico" class="h4">Total vendido, en Bs</h2>
        <canvas id="graficoComparacion" height="200" role="img"
                aria-label="Gráfico de barras del total vendido: periodo A {{ number_format($a['total'], 2, ',', '.') }} bolivianos, periodo B {{ number_format($b['total'], 2, ',', '.') }} bolivianos. Los datos están en la tabla."></canvas>
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

        new Chart(document.getElementById('graficoComparacion'), {
            type: 'bar',
            data: {
                labels: ['Periodo A', 'Periodo B'],
                datasets: [{ data: @json([$a['total'], $b['total']]), backgroundColor: color('--grafico'), borderRadius: 4, maxBarThickness: 64 }]
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
