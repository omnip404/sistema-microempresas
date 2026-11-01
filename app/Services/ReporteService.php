<?php

namespace App\Services;

use App\Models\DetalleVenta;
use App\Models\Venta;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Cálculos de los reportes (RF09). Solo lee datos; la exportación CSV y la
 * pantalla usan los mismos números para que nunca difieran.
 */
class ReporteService
{
    /**
     * Ventas REGISTRADAS del periodo, agrupadas por día.
     *
     * @return array{filas: Collection<int, array{fecha: string, ventas: int, unidades: int, total: float}>, totales: array{ventas: int, unidades: int, total: float}}
     */
    public function ventasPorDia(Carbon $desde, Carbon $hasta): array
    {
        $porDia = Venta::where('estado', Venta::REGISTRADA)
            ->whereBetween('fecha', [$desde, $hasta])
            ->selectRaw('DATE(fecha) as dia, COUNT(*) as ventas, SUM(total) as total')
            ->groupBy('dia')
            ->orderBy('dia')
            ->get()
            ->keyBy('dia');

        $unidades = DetalleVenta::query()
            ->join('ventas', 'ventas.id', '=', 'detalle_ventas.venta_id')
            ->where('ventas.estado', Venta::REGISTRADA)
            ->whereBetween('ventas.fecha', [$desde, $hasta])
            ->selectRaw('DATE(ventas.fecha) as dia, SUM(detalle_ventas.cantidad) as unidades')
            ->groupBy('dia')
            ->pluck('unidades', 'dia');

        $filas = $porDia->map(fn ($f, $dia) => [
            'fecha' => $dia,
            'ventas' => (int) $f->ventas,
            'unidades' => (int) ($unidades[$dia] ?? 0),
            'total' => (float) $f->total,
        ])->values();

        return [
            'filas' => $filas,
            'totales' => [
                'ventas' => $filas->sum('ventas'),
                'unidades' => $filas->sum('unidades'),
                'total' => (float) $filas->sum('total'),
            ],
        ];
    }
}
