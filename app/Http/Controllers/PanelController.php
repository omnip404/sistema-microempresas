<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Services\ReporteService;

/**
 * Panel estadístico del Administrador (RF10, RF08). Los cálculos están en ReporteService.
 */
class PanelController extends Controller
{
    public function __construct(private ReporteService $reportes) {}

    public function index()
    {
        $ventasHoy = $this->reportes->resumenVentas(now()->startOfDay(), now()->endOfDay())['total'];
        $ventasMes = $this->reportes->resumenVentas(now()->startOfMonth(), now())['total'];
        $gananciaMes = $this->reportes->gananciaEstimada(now()->startOfMonth(), now());

        // Ventas de los últimos 7 días para el gráfico de Chart.js
        $grafico = $this->reportes->ventasUltimosDias(7);
        $masVendidos = $this->reportes->productosMasVendidos(5);
        $stockBajo = Producto::stockBajo()->orderBy('stock')->get();

        return view('panel.index', compact(
            'ventasHoy', 'ventasMes', 'gananciaMes', 'grafico', 'masVendidos', 'stockBajo'
        ));
    }
}
