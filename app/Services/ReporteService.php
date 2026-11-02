<?php

namespace App\Services;

use App\Models\Compra;
use App\Models\DetalleCompra;
use App\Models\DetalleVenta;
use App\Models\Producto;
use App\Models\Venta;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Cálculos de los reportes (RF09). Solo lee datos; la pantalla y la exportación
 * CSV usan los mismos números para que nunca difieran.
 *
 * Cada reporte devuelve ['filas' => [...], 'totales' => [...]|null, 'columnas' => [...]],
 * donde cada columna es [clave, etiqueta, formato] y el formato es texto, entero, monto o fecha.
 */
class ReporteService
{
    public const VENTAS = 'ventas';

    public const INVENTARIO = 'inventario';

    public const STOCK_BAJO = 'stock-bajo';

    public const MAS_VENDIDOS = 'mas-vendidos';

    public const COMPRAS = 'compras';

    /** Tipos de reporte y si usan el periodo desde-hasta. */
    public const TIPOS = [
        self::VENTAS => ['Ventas por día', true],
        self::INVENTARIO => ['Inventario actual valorizado', false],
        self::STOCK_BAJO => ['Productos con stock bajo', false],
        self::MAS_VENDIDOS => ['Productos más vendidos', true],
        self::COMPRAS => ['Compras por periodo', true],
    ];

    public const LIMITE_MAS_VENDIDOS = 10;

    public function generar(string $tipo, Carbon $desde, Carbon $hasta, ?int $proveedorId = null): array
    {
        return match ($tipo) {
            self::INVENTARIO => $this->inventarioValorizado(),
            self::STOCK_BAJO => $this->stockBajo(),
            self::MAS_VENDIDOS => $this->masVendidos($desde, $hasta),
            self::COMPRAS => $this->comprasPorPeriodo($desde, $hasta, $proveedorId),
            default => $this->ventasPorDia($desde, $hasta),
        };
    }

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
            'columnas' => [
                ['fecha', 'Fecha', 'fecha'],
                ['ventas', 'N.º de ventas', 'entero'],
                ['unidades', 'Unidades', 'entero'],
                ['total', 'Total (Bs)', 'monto'],
            ],
            'filas' => $filas,
            'totales' => [
                'ventas' => $filas->sum('ventas'),
                'unidades' => $filas->sum('unidades'),
                'total' => (float) $filas->sum('total'),
            ],
        ];
    }

    /**
     * Total, cantidad y promedio de las ventas REGISTRADAS de un periodo.
     *
     * @return array{total: float, ventas: int, promedio: float}
     */
    public function resumenVentas(Carbon $desde, Carbon $hasta): array
    {
        $resumen = Venta::where('estado', Venta::REGISTRADA)
            ->whereBetween('fecha', [$desde, $hasta])
            ->selectRaw('COUNT(*) as ventas, COALESCE(SUM(total), 0) as total')
            ->first();

        $ventas = (int) $resumen->ventas;
        $total = (float) $resumen->total;

        return ['total' => $total, 'ventas' => $ventas, 'promedio' => $ventas > 0 ? round($total / $ventas, 2) : 0.0];
    }

    /**
     * Compara el periodo A con el B. La variación es el cambio % de A respecto de B;
     * si B vale cero no se puede calcular y queda en null (nunca se divide entre cero).
     *
     * @return array{a: array, b: array, variacion: array{total: ?float, ventas: ?float, promedio: ?float}}
     */
    public function compararPeriodos(Carbon $desdeA, Carbon $hastaA, Carbon $desdeB, Carbon $hastaB): array
    {
        $a = $this->resumenVentas($desdeA, $hastaA);
        $b = $this->resumenVentas($desdeB, $hastaB);

        $variacion = [];
        foreach (['total', 'ventas', 'promedio'] as $clave) {
            $variacion[$clave] = $b[$clave] > 0 ? round(($a[$clave] - $b[$clave]) / $b[$clave] * 100, 1) : null;
        }

        return ['a' => $a, 'b' => $b, 'variacion' => $variacion];
    }

    /** Productos activos con su stock valorizado a precio de compra (stock × precio de compra). */
    public function inventarioValorizado(): array
    {
        $filas = Producto::with('categoria')->where('activo', true)->orderBy('nombre')->get()
            ->map(fn (Producto $p) => [
                'codigo' => $p->codigo,
                'nombre' => $p->nombre,
                'categoria' => $p->categoria->nombre,
                'stock' => $p->stock,
                'precio_compra' => (float) $p->precio_compra,
                'valor' => round($p->stock * (float) $p->precio_compra, 2),
            ])->values();

        return [
            'columnas' => [
                ['codigo', 'Código', 'texto'],
                ['nombre', 'Producto', 'texto'],
                ['categoria', 'Categoría', 'texto'],
                ['stock', 'Stock', 'entero'],
                ['precio_compra', 'Precio de compra (Bs)', 'monto'],
                ['valor', 'Valor (Bs)', 'monto'],
            ],
            'filas' => $filas,
            'totales' => ['stock' => $filas->sum('stock'), 'valor' => round($filas->sum('valor'), 2)],
        ];
    }

    /** Productos activos cuyo stock llegó al mínimo, los de menor stock primero. */
    public function stockBajo(): array
    {
        $filas = Producto::with('categoria')->stockBajo()->get()
            ->sortBy([['stock', 'asc'], ['nombre', 'asc']])
            ->map(fn (Producto $p) => [
                'codigo' => $p->codigo,
                'nombre' => $p->nombre,
                'categoria' => $p->categoria->nombre,
                'stock' => $p->stock,
                'stock_minimo' => $p->stock_minimo,
            ])->values();

        return [
            'columnas' => [
                ['codigo', 'Código', 'texto'],
                ['nombre', 'Producto', 'texto'],
                ['categoria', 'Categoría', 'texto'],
                ['stock', 'Stock', 'entero'],
                ['stock_minimo', 'Stock mínimo', 'entero'],
            ],
            'filas' => $filas,
            'totales' => null,
        ];
    }

    /**
     * Compras REGISTRADAS del periodo (RF09.2), una fila por compra, con filtro opcional por proveedor.
     * Las anuladas no se incluyen.
     */
    public function comprasPorPeriodo(Carbon $desde, Carbon $hasta, ?int $proveedorId = null): array
    {
        $unidades = DetalleCompra::query()
            ->join('compras', 'compras.id', '=', 'detalle_compras.compra_id')
            ->where('compras.estado', Compra::REGISTRADA)
            ->whereBetween('compras.fecha', [$desde, $hasta])
            ->when($proveedorId, fn ($q) => $q->where('compras.proveedor_id', $proveedorId))
            ->selectRaw('detalle_compras.compra_id, SUM(detalle_compras.cantidad) as unidades')
            ->groupBy('detalle_compras.compra_id')
            ->pluck('unidades', 'compra_id');

        $filas = Compra::with('proveedor')
            ->where('estado', Compra::REGISTRADA)
            ->whereBetween('fecha', [$desde, $hasta])
            ->when($proveedorId, fn ($q) => $q->where('proveedor_id', $proveedorId))
            ->orderBy('fecha')->orderBy('id')
            ->get()
            ->map(fn (Compra $c) => [
                'fecha' => $c->fecha->toDateString(),
                'nro_comprobante' => $c->nro_comprobante ?? '',
                'proveedor' => $c->proveedor->nombre,
                'unidades' => (int) ($unidades[$c->id] ?? 0),
                'total' => (float) $c->total,
            ])->values();

        return [
            'columnas' => [
                ['fecha', 'Fecha', 'fecha'],
                ['nro_comprobante', 'N.º de comprobante', 'texto'],
                ['proveedor', 'Proveedor', 'texto'],
                ['unidades', 'Unidades', 'entero'],
                ['total', 'Total (Bs)', 'monto'],
            ],
            'filas' => $filas,
            'totales' => [
                'unidades' => $filas->sum('unidades'),
                'total' => round((float) $filas->sum('total'), 2),
            ],
        ];
    }

    /** Los productos con más unidades vendidas en el periodo (solo ventas REGISTRADAS). */
    public function masVendidos(Carbon $desde, Carbon $hasta): array
    {
        $filas = DetalleVenta::query()
            ->join('ventas', 'ventas.id', '=', 'detalle_ventas.venta_id')
            ->join('productos', 'productos.id', '=', 'detalle_ventas.producto_id')
            ->where('ventas.estado', Venta::REGISTRADA)
            ->whereBetween('ventas.fecha', [$desde, $hasta])
            ->selectRaw('productos.codigo, productos.nombre, SUM(detalle_ventas.cantidad) as unidades, SUM(detalle_ventas.cantidad * detalle_ventas.precio_unitario) as total')
            ->groupBy('productos.id', 'productos.codigo', 'productos.nombre')
            ->orderByDesc('unidades')
            ->orderBy('productos.nombre')
            ->limit(self::LIMITE_MAS_VENDIDOS)
            ->get()
            ->map(fn ($f) => [
                'codigo' => $f->codigo,
                'nombre' => $f->nombre,
                'unidades' => (int) $f->unidades,
                'total' => round((float) $f->total, 2),
            ])->values();

        return [
            'columnas' => [
                ['codigo', 'Código', 'texto'],
                ['nombre', 'Producto', 'texto'],
                ['unidades', 'Unidades vendidas', 'entero'],
                ['total', 'Total vendido (Bs)', 'monto'],
            ],
            'filas' => $filas,
            'totales' => null,
        ];
    }
}
