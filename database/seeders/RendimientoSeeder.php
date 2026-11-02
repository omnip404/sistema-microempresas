<?php

namespace Database\Seeders;

use App\Models\Categoria;
use App\Models\Producto;
use App\Models\Usuario;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Carga de rendimiento (RNF01): crea 10 000 ventas con 1 a 3 productos cada una, repartidas en los
 * últimos 12 meses (≈ 3 % anuladas). NO se ejecuta desde DatabaseSeeder; se usa a mano o desde
 * tests/Feature/RendimientoTest.php:
 *
 *   php artisan db:seed --class=RendimientoSeeder   (después de DatabaseSeeder)
 *
 * No toca el stock: son datos para medir tiempos, no un historial coherente de inventario.
 * Es determinista (semilla fija), así que dos corridas dan los mismos montos.
 */
class RendimientoSeeder extends Seeder
{
    public const VENTAS = 10_000;

    private const PRODUCTOS_EXTRA = 60;

    public function run(): void
    {
        $usuario = Usuario::first() ?? throw new \RuntimeException('Ejecute primero DatabaseSeeder: no hay usuarios.');

        mt_srand(2026);

        $productos = $this->productos();
        $primerId = (int) DB::table('ventas')->max('id') + 1;
        $ahora = now();

        $ventas = [];
        $detalles = [];

        for ($i = 0; $i < self::VENTAS; $i++) {
            $id = $primerId + $i;
            $fecha = $ahora->copy()->subSeconds(mt_rand(0, 364 * 86_400));
            $total = 0.0;

            foreach ((array) array_rand($productos, mt_rand(1, 3)) as $clave) {
                $producto = $productos[$clave];
                $cantidad = mt_rand(1, 6);
                $total += $cantidad * $producto['precio_venta'];
                $detalles[] = [
                    'venta_id' => $id, 'producto_id' => $producto['id'],
                    'cantidad' => $cantidad, 'precio_unitario' => $producto['precio_venta'],
                ];
            }

            $ventas[] = [
                'id' => $id, 'usuario_id' => $usuario->id, 'cliente_id' => null,
                'fecha' => $fecha->toDateTimeString(), 'total' => round($total, 2),
                'estado' => mt_rand(1, 100) <= 3 ? 'ANULADA' : 'REGISTRADA',
                'created_at' => $fecha->toDateTimeString(), 'updated_at' => $fecha->toDateTimeString(),
            ];
        }

        DB::transaction(function () use ($ventas, $detalles) {
            foreach (array_chunk($ventas, 100) as $lote) {
                DB::table('ventas')->insert($lote);
            }
            foreach (array_chunk($detalles, 150) as $lote) {
                DB::table('detalle_ventas')->insert($lote);
            }
        });
    }

    /** Catálogo ampliado (los productos de ejemplo más 60 nuevos repartidos en las categorías). */
    private function productos(): array
    {
        $categorias = Categoria::pluck('id');
        if ($categorias->isEmpty()) {
            throw new \RuntimeException('Ejecute primero DatabaseSeeder: no hay categorías.');
        }

        for ($n = 1; $n <= self::PRODUCTOS_EXTRA; $n++) {
            Producto::firstOrCreate(['codigo' => sprintf('RND-%03d', $n)], [
                'categoria_id' => $categorias[$n % $categorias->count()],
                'nombre' => "Producto de carga {$n}",
                'unidad_medida' => 'unidad',
                'precio_compra' => 5 + $n % 20,
                'precio_venta' => 8 + $n % 30,
                'stock' => 100,
                'stock_minimo' => 5,
            ]);
        }

        return Producto::get(['id', 'precio_venta'])
            ->map(fn ($p) => ['id' => $p->id, 'precio_venta' => (float) $p->precio_venta])
            ->values()
            ->all();
    }
}
