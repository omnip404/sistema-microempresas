<?php

namespace App\Http\Controllers;

use App\Models\DetalleVenta;
use App\Models\Producto;
use App\Models\Venta;
use Illuminate\Support\Facades\DB;

/**
 * Panel estadístico del Administrador (RF10, RF08).
 */
class PanelController extends Controller
{
    public function index()
    {
        $registradas = Venta::where('estado', Venta::REGISTRADA);

        $ventasHoy = (clone $registradas)->whereDate('fecha', today())->sum('total');
        $ventasMes = (clone $registradas)->whereBetween('fecha', [now()->startOfMonth(), now()])->sum('total');
        $cantidadMes = (clone $registradas)->whereBetween('fecha', [now()->startOfMonth(), now()])->count();
        $ticketPromedio = $cantidadMes > 0 ? $ventasMes / $cantidadMes : 0;

        // Ventas de los últimos 7 días para el gráfico de Chart.js
        $ultimos7 = (clone $registradas)
            ->where('fecha', '>=', today()->subDays(6))
            ->selectRaw('DATE(fecha) as dia, SUM(total) as total')
            ->groupBy('dia')
            ->pluck('total', 'dia');

        $dias = collect(range(6, 0))->map(fn ($n) => today()->subDays($n)->toDateString());
        $grafico = [
            'etiquetas' => $dias->map(fn ($d) => date('d/m', strtotime($d))),
            'valores' => $dias->map(fn ($d) => (float) ($ultimos7[$d] ?? 0)),
        ];

        $masVendidos = DetalleVenta::query()
            ->join('ventas', 'ventas.id', '=', 'detalle_ventas.venta_id')
            ->join('productos', 'productos.id', '=', 'detalle_ventas.producto_id')
            ->where('ventas.estado', Venta::REGISTRADA)
            ->select('productos.nombre', DB::raw('SUM(detalle_ventas.cantidad) as unidades'))
            ->groupBy('productos.nombre')
            ->orderByDesc('unidades')
            ->limit(5)
            ->get();

        $stockBajo = Producto::stockBajo()->orderBy('stock')->get();

        return view('panel.index', compact(
            'ventasHoy', 'ventasMes', 'ticketPromedio', 'grafico', 'masVendidos', 'stockBajo'
        ));
    }
}
