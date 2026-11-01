<?php

namespace App\Http\Controllers;

use App\Http\Requests\PeriodoRequest;
use App\Services\ReporteService;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Reportes (RF09). Solo Administrador. La exportación es un CSV pensado para
 * Excel en español: UTF-8 con BOM, separador «;» y coma decimal.
 */
class ReporteController extends Controller
{
    public function __construct(private ReporteService $reportes) {}

    public function index(PeriodoRequest $request)
    {
        return view('reportes.index', [
            'desde' => $request->desde(),
            'hasta' => $request->hasta(),
            'reporte' => $this->reportes->ventasPorDia($request->desde(), $request->hasta()),
        ]);
    }

    public function exportar(PeriodoRequest $request): StreamedResponse
    {
        $reporte = $this->reportes->ventasPorDia($request->desde(), $request->hasta());

        $filas = [['Fecha', 'N.º de ventas', 'Unidades', 'Total (Bs)']];
        foreach ($reporte['filas'] as $f) {
            $filas[] = [
                Carbon::parse($f['fecha'])->format('d/m/Y'), $f['ventas'], $f['unidades'], $this->monto($f['total']),
            ];
        }
        $filas[] = ['Total', $reporte['totales']['ventas'], $reporte['totales']['unidades'], $this->monto($reporte['totales']['total'])];

        $nombre = 'ventas-'.$request->desde()->toDateString().'_'.$request->hasta()->toDateString().'.csv';

        return $this->csv($nombre, $filas);
    }

    /** Monto con coma decimal y sin separador de miles, como lo espera Excel en español. */
    private function monto(float $valor): string
    {
        return number_format($valor, 2, ',', '');
    }

    private function csv(string $nombre, array $filas): StreamedResponse
    {
        return response()->streamDownload(function () use ($filas) {
            $salida = fopen('php://output', 'w');
            fwrite($salida, "\xEF\xBB\xBF");   // BOM: Excel reconoce así el UTF-8

            foreach ($filas as $fila) {
                fputcsv($salida, $fila, ';');
            }

            fclose($salida);
        }, $nombre, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
