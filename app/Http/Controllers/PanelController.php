<?php

namespace App\Http\Controllers;

use App\Http\Requests\PeriodoRequest;
use App\Models\Producto;
use App\Services\ReporteService;

/**
 * Panel estadístico del Administrador (RF10, RF08). Los cálculos están en ReporteService.
 * Los indicadores son del mes actual; los gráficos siguen el filtro desde-hasta (por defecto, el mes).
 */
class PanelController extends Controller
{
    public function __construct(private ReporteService $reportes) {}

    public function index(PeriodoRequest $request)
    {
        $desde = $request->desde();
        $hasta = $request->hasta();

        $ventasHoy = $this->reportes->resumenVentas(now()->startOfDay(), now()->endOfDay())['total'];
        $ventasMes = $this->reportes->resumenVentas(now()->startOfMonth(), now())['total'];
        $gananciaMes = $this->reportes->gananciaEstimada(now()->startOfMonth(), now());

        $lineas = $this->reportes->ventasDiariasPeriodo($desde, $hasta);
        $masVendidos = $this->reportes->masVendidos($desde, $hasta)['filas'];
        $categorias = $this->reportes->ventasPorCategoria($desde, $hasta);
        $stockBajo = Producto::stockBajo()->orderBy('stock')->get();

        return view('panel.index', compact(
            'desde', 'hasta', 'ventasHoy', 'ventasMes', 'gananciaMes', 'lineas', 'masVendidos', 'categorias', 'stockBajo'
        ));
    }
}
