<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\DetalleVenta;
use App\Models\Producto;
use App\Models\Usuario;
use App\Models\Venta;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReporteInventarioTest extends TestCase
{
    use RefreshDatabase;

    private Producto $a;

    private Producto $b;

    private Producto $c;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);

        // Catálogo controlado: se dan de baja los productos de ejemplo
        Producto::query()->update(['activo' => false]);
        $categoria = Categoria::first();
        $this->a = $this->producto($categoria, 'T-001', 'Arroz test', stock: 10, minimo: 5, compra: 4.50);
        $this->b = $this->producto($categoria, 'T-002', 'Aceite test', stock: 3, minimo: 5, compra: 12.00);
        $this->c = $this->producto($categoria, 'T-003', 'Azúcar test', stock: 0, minimo: 2, compra: 6.00);
    }

    private function producto(Categoria $categoria, string $codigo, string $nombre, int $stock, int $minimo, float $compra): Producto
    {
        return Producto::create([
            'categoria_id' => $categoria->id, 'codigo' => $codigo, 'nombre' => $nombre, 'unidad_medida' => 'unidad',
            'precio_compra' => $compra, 'precio_venta' => $compra * 2, 'stock' => $stock, 'stock_minimo' => $minimo, 'activo' => true,
        ]);
    }

    private function admin(): Usuario
    {
        return Usuario::where('email', 'admin@microempresa.test')->first();
    }

    private function vender(Producto $producto, int $unidades, float $precio, string $fecha, string $estado = Venta::REGISTRADA): void
    {
        $venta = Venta::create([
            'usuario_id' => $this->admin()->id, 'fecha' => $fecha, 'total' => $unidades * $precio, 'estado' => $estado,
        ]);
        DetalleVenta::create(['venta_id' => $venta->id, 'producto_id' => $producto->id, 'cantidad' => $unidades, 'precio_unitario' => $precio]);
    }

    private function csv(string $tipo, array $extra = []): array
    {
        $contenido = $this->actingAs($this->admin())
            ->get(route('reportes.exportar', ['tipo' => $tipo] + $extra))
            ->assertOk()
            ->streamedContent();

        $this->assertStringStartsWith("\xEF\xBB\xBF", $contenido);

        return preg_split('/\r?\n/', trim(substr($contenido, 3)));
    }

    public function test_inventario_valorizado_calcula_stock_por_precio_de_compra_y_total(): void
    {
        $reporte = $this->actingAs($this->admin())->get(route('reportes.index', ['tipo' => 'inventario']))
            ->assertOk()
            ->assertSee('Inventario actual valorizado')
            ->assertSee('Arroz test')
            ->assertViewHas('reporte')
            ->viewData('reporte');

        $valores = $reporte['filas']->pluck('valor', 'codigo')->all();
        $this->assertEquals(['T-002' => 36.0, 'T-001' => 45.0, 'T-003' => 0.0], $valores);   // 3×12, 10×4,50, 0×6
        $this->assertEquals(['stock' => 13, 'valor' => 81.0], $reporte['totales']);
    }

    public function test_inventario_valorizado_ignora_productos_dados_de_baja(): void
    {
        $this->actingAs($this->admin())->get(route('reportes.index', ['tipo' => 'inventario']))
            ->assertViewHas('reporte', fn ($r) => $r['filas']->count() === 3);
    }

    public function test_csv_de_inventario(): void
    {
        $lineas = $this->csv('inventario');

        $this->assertEquals('Código;Producto;Categoría;Stock;"Precio de compra (Bs)";"Valor (Bs)"', $lineas[0]);
        $this->assertStringContainsString('T-001;"Arroz test"', $lineas[2]);
        $this->assertStringEndsWith(';10;4,50;45,00', $lineas[2]);
        $this->assertEquals('Total;;;13;;81,00', end($lineas));
    }

    public function test_stock_bajo_lista_solo_productos_activos_en_o_bajo_el_minimo(): void
    {
        $reporte = $this->actingAs($this->admin())->get(route('reportes.index', ['tipo' => 'stock-bajo']))
            ->assertOk()
            ->assertSee('Productos con stock bajo')
            ->viewData('reporte');

        // Arroz (10 > 5) no aparece; Azúcar (stock 0) va antes que Aceite (stock 3)
        $this->assertEquals(['T-003', 'T-002'], $reporte['filas']->pluck('codigo')->all());
        $this->assertNull($reporte['totales']);
    }

    public function test_csv_de_stock_bajo(): void
    {
        $lineas = $this->csv('stock-bajo');

        $this->assertEquals('Código;Producto;Categoría;Stock;"Stock mínimo"', $lineas[0]);
        $this->assertCount(3, $lineas);
        $this->assertStringStartsWith('T-003;', $lineas[1]);
        $this->assertStringEndsWith(';0;2', $lineas[1]);
    }

    public function test_mas_vendidos_ordena_por_unidades_y_respeta_periodo_y_estado(): void
    {
        $this->vender($this->a, 3, 9.00, '2026-03-05 10:00:00');
        $this->vender($this->a, 4, 9.00, '2026-03-20 10:00:00');       // Arroz: 7 unidades
        $this->vender($this->b, 5, 24.00, '2026-03-06 10:00:00');      // Aceite: 5 unidades
        $this->vender($this->b, 50, 24.00, '2026-03-07 10:00:00', Venta::ANULADA);   // anulada: no cuenta
        $this->vender($this->c, 40, 12.00, '2026-04-02 10:00:00');     // fuera del periodo

        $reporte = $this->actingAs($this->admin())
            ->get(route('reportes.index', ['tipo' => 'mas-vendidos', 'desde' => '2026-03-01', 'hasta' => '2026-03-31']))
            ->assertOk()
            ->assertSee('Productos más vendidos')
            ->viewData('reporte');

        $this->assertEquals(['T-001', 'T-002'], $reporte['filas']->pluck('codigo')->all());
        $this->assertEquals([7, 5], $reporte['filas']->pluck('unidades')->all());
        $this->assertEquals([63.0, 120.0], $reporte['filas']->pluck('total')->all());
    }

    public function test_mas_vendidos_muestra_como_maximo_diez_productos(): void
    {
        $categoria = Categoria::first();
        foreach (range(1, 12) as $i) {
            $this->vender($this->producto($categoria, "M-$i", "Producto $i", 5, 1, 1.0), $i, 2.00, now()->toDateTimeString());
        }

        $this->actingAs($this->admin())->get(route('reportes.index', ['tipo' => 'mas-vendidos']))
            ->assertViewHas('reporte', fn ($r) => $r['filas']->count() === 10 && $r['filas'][0]['codigo'] === 'M-12');
    }

    public function test_csv_de_mas_vendidos(): void
    {
        $this->vender($this->a, 7, 9.00, '2026-03-05 10:00:00');

        $lineas = $this->csv('mas-vendidos', ['desde' => '2026-03-01', 'hasta' => '2026-03-31']);

        $this->assertEquals('Código;Producto;"Unidades vendidas";"Total vendido (Bs)"', $lineas[0]);
        $this->assertEquals('T-001;"Arroz test";7;63,00', $lineas[1]);
        $this->assertCount(2, $lineas);
    }

    public function test_tipo_desconocido_se_rechaza_y_vendedor_recibe_403(): void
    {
        $this->actingAs($this->admin())->get(route('reportes.index', ['tipo' => 'secreto']))
            ->assertSessionHasErrors('tipo');

        $vendedor = Usuario::where('email', 'vendedor@microempresa.test')->first();
        foreach (['inventario', 'stock-bajo', 'mas-vendidos'] as $tipo) {
            $this->actingAs($vendedor)->get(route('reportes.index', ['tipo' => $tipo]))->assertForbidden();
            $this->actingAs($vendedor)->get(route('reportes.exportar', ['tipo' => $tipo]))->assertForbidden();
        }
    }
}
