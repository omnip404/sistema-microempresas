<?php

namespace App\Http\Controllers;

use App\Http\Requests\ComparacionRequest;
use App\Services\ReporteService;

/**
 * Comparación de ventas entre dos periodos (RF11). Solo Administrador.
 */
class ComparacionController extends Controller
{
    public function __construct(private ReporteService $reportes) {}

    public function index(ComparacionRequest $request)
    {
        $desdeA = $request->periodo('a', true);
        $hastaA = $request->periodo('a', false);
        $desdeB = $request->periodo('b', true);
        $hastaB = $request->periodo('b', false);

        return view('comparacion.index', [
            'desdeA' => $desdeA, 'hastaA' => $hastaA,
            'desdeB' => $desdeB, 'hastaB' => $hastaB,
            'comparacion' => $this->reportes->compararPeriodos($desdeA, $hastaA, $desdeB, $hastaB),
        ]);
    }
}
