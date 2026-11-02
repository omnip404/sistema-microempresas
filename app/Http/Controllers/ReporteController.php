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
        $tipo = $request->tipo();

        return view('reportes.index', [
            'tipo' => $tipo,
            'tipos' => ReporteService::TIPOS,
            'usaPeriodo' => ReporteService::TIPOS[$tipo][1],
            'desde' => $request->desde(),
            'hasta' => $request->hasta(),
            'reporte' => $this->reportes->generar($tipo, $request->desde(), $request->hasta()),
        ]);
    }

    public function exportar(PeriodoRequest $request): StreamedResponse
    {
        $tipo = $request->tipo();
        $reporte = $this->reportes->generar($tipo, $request->desde(), $request->hasta());
        $columnas = $reporte['columnas'];

        $filas = [array_column($columnas, 1)];
        foreach ($reporte['filas'] as $fila) {
            $filas[] = array_map(fn ($c) => $this->celda($fila[$c[0]], $c[2]), $columnas);
        }

        if ($reporte['totales'] !== null) {
            $filas[] = array_map(
                fn ($c, $i) => $i === 0 ? 'Total' : (isset($reporte['totales'][$c[0]]) ? $this->celda($reporte['totales'][$c[0]], $c[2]) : ''),
                $columnas, array_keys($columnas)
            );
        }

        $sufijo = ReporteService::TIPOS[$tipo][1]
            ? $request->desde()->toDateString().'_'.$request->hasta()->toDateString()
            : now()->toDateString();

        return $this->csv("{$tipo}-{$sufijo}.csv", $filas);
    }

    /** Valor tal como lo espera Excel en español: fechas d/m/Y y montos con coma decimal sin miles. */
    private function celda(mixed $valor, string $formato): string|int
    {
        return match ($formato) {
            'fecha' => Carbon::parse($valor)->format('d/m/Y'),
            'monto' => number_format((float) $valor, 2, ',', ''),
            default => $this->sinFormula($valor),
        };
    }

    /** Texto libre: si empieza con = + - @ tab o retorno, Excel lo evaluaría como fórmula; se antepone una comilla. */
    private function sinFormula(mixed $valor): mixed
    {
        if (is_string($valor) && ! is_numeric($valor) && preg_match('/^[=+\-@\t\r]/', $valor)) {
            return "'".$valor;
        }

        return $valor;
    }

    private function csv(string $nombre, array $filas): StreamedResponse
    {
        return response()->streamDownload(function () use ($filas) {
            $salida = fopen('php://output', 'w');
            fwrite($salida, "\xEF\xBB\xBF");   // BOM: Excel reconoce así el UTF-8

            foreach ($filas as $fila) {
                // Sin carácter de escape: con el `\` por defecto, `\"` no se duplica y permite saltar de columna
                fputcsv($salida, $fila, ';', '"', '');
            }

            fclose($salida);
        }, $nombre, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
