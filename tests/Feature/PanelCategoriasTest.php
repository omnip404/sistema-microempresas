<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\DetalleVenta;
use App\Models\Producto;
use App\Models\Usuario;
use App\Models\Venta;
use App\Services\ReporteService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PanelCategoriasTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        Carbon::setTestNow('2026-10-20 15:00:00');

        Producto::query()->update(['activo' => false]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function admin(): Usuario
    {
        return Usuario::where('email', 'admin@microempresa.test')->first();
    }

    /** Crea una categoría con un producto y le registra una venta del monto indicado. */
    private function categoriaConVenta(string $nombre, float $monto, string $fecha = '2026-10-10 10:00:00', string $estado = Venta::REGISTRADA): void
    {
        $categoria = Categoria::firstOrCreate(['nombre' => $nombre]);
        $producto = Producto::create([
            'categoria_id' => $categoria->id, 'codigo' => 'C-'.$categoria->id.'-'.Producto::count(), 'nombre' => "Producto de {$nombre}",
            'unidad_medida' => 'unidad', 'precio_compra' => 1, 'precio_venta' => 2, 'stock' => 100, 'stock_minimo' => 1, 'activo' => true,
        ]);
        $venta = Venta::create(['usuario_id' => $this->admin()->id, 'fecha' => $fecha, 'total' => $monto, 'estado' => $estado]);
        DetalleVenta::create(['venta_id' => $venta->id, 'producto_id' => $producto->id, 'cantidad' => 2, 'precio_unitario' => $monto / 2]);
    }

    public function test_agrupa_por_categoria_con_porcentajes_y_excluye_anuladas_y_otros_periodos(): void
    {
        $this->categoriaConVenta('Bebidas ZZ', 300);
        $this->categoriaConVenta('Abarrotes ZZ', 100);
        $this->categoriaConVenta('Lácteos ZZ', 600, estado: Venta::ANULADA);          // no cuenta
        $this->categoriaConVenta('Limpieza ZZ', 900, fecha: '2026-08-01 10:00:00');   // fuera del periodo

        $datos = app(ReporteService::class)->ventasPorCategoria(now()->startOfMonth(), now());

        $this->assertSame(400.0, $datos['total']);
        $this->assertSame(['Bebidas ZZ', 'Abarrotes ZZ'], array_column($datos['filas'], 'categoria'));
        $this->assertSame([300.0, 100.0], array_column($datos['filas'], 'total'));
        $this->assertSame([75.0, 25.0], array_column($datos['filas'], 'porcentaje'));
    }

    public function test_las_categorias_menores_se_suman_en_otras(): void
    {
        foreach ([700, 600, 500, 400, 300, 200, 100] as $i => $monto) {
            $this->categoriaConVenta("Cat {$i}", $monto);
        }

        $datos = app(ReporteService::class)->ventasPorCategoria(now()->startOfMonth(), now(), maximo: 5);

        $this->assertCount(6, $datos['filas']);
        $this->assertSame('Otras', $datos['filas'][5]['categoria']);
        $this->assertSame(300.0, $datos['filas'][5]['total']);       // 200 + 100
        $this->assertSame(2800.0, $datos['total']);
        $this->assertEquals(100.0, round(array_sum(array_column($datos['filas'], 'porcentaje')), 1));
    }

    public function test_sin_ventas_no_hay_filas_ni_division_entre_cero(): void
    {
        $datos = app(ReporteService::class)->ventasPorCategoria(now()->startOfMonth(), now());

        $this->assertSame([], $datos['filas']);
        $this->assertSame(0.0, $datos['total']);
    }

    public function test_el_panel_muestra_grafico_leyenda_y_tabla_alternativa(): void
    {
        $this->categoriaConVenta('Bebidas ZZ', 300);
        $this->categoriaConVenta('Abarrotes ZZ', 100);

        $this->actingAs($this->admin())->get(route('panel'))
            ->assertOk()
            ->assertSee('id="graficoCategorias"', false)
            ->assertSee('Leyenda del gráfico circular')
            ->assertSee('Bs 300,00 · 75,0 %')
            ->assertSee('Ventas por categoría del periodo')
            ->assertViewHas('categorias', fn ($c) => $c['total'] === 400.0);
    }

    public function test_el_panel_sin_ventas_en_el_periodo_lo_indica(): void
    {
        $this->actingAs($this->admin())
            ->get(route('panel', ['desde' => '2020-01-01', 'hasta' => '2020-01-31']))
            ->assertOk()
            ->assertDontSee('id="graficoCategorias"', false)
            ->assertSee('No hay ventas registradas en este periodo.');
    }
}
