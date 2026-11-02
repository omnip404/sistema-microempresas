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

class PanelGraficosTest extends TestCase
{
    use RefreshDatabase;

    private Categoria $categoria;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        Carbon::setTestNow('2026-10-20 15:00:00');

        Producto::query()->update(['activo' => false]);
        $this->categoria = Categoria::first();
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

    private function producto(string $codigo, string $nombre): Producto
    {
        return Producto::create([
            'categoria_id' => $this->categoria->id, 'codigo' => $codigo, 'nombre' => $nombre, 'unidad_medida' => 'unidad',
            'precio_compra' => 1, 'precio_venta' => 2, 'stock' => 500, 'stock_minimo' => 1, 'activo' => true,
        ]);
    }

    private function vender(Producto $producto, int $unidades, float $precio, string $fecha, string $estado = Venta::REGISTRADA): void
    {
        $venta = Venta::create([
            'usuario_id' => $this->admin()->id, 'fecha' => $fecha, 'total' => $unidades * $precio, 'estado' => $estado,
        ]);
        DetalleVenta::create(['venta_id' => $venta->id, 'producto_id' => $producto->id, 'cantidad' => $unidades, 'precio_unitario' => $precio]);
    }

    public function test_lineas_de_ventas_por_dia_rellenan_con_cero_los_dias_sin_ventas(): void
    {
        $p = $this->producto('P-001', 'Producto uno');
        $this->vender($p, 2, 10, '2026-10-01 09:00:00');                         // 20
        $this->vender($p, 1, 5, '2026-10-01 17:00:00');                          // 5  → 25 el día 1
        $this->vender($p, 3, 10, '2026-10-03 09:00:00');                         // 30
        $this->vender($p, 9, 10, '2026-10-02 09:00:00', Venta::ANULADA);         // no cuenta
        $this->vender($p, 9, 10, '2026-10-05 09:00:00');                         // fuera del periodo

        $datos = app(ReporteService::class)->ventasDiariasPeriodo(Carbon::parse('2026-10-01')->startOfDay(), Carbon::parse('2026-10-04')->endOfDay());

        $this->assertSame(['01/10', '02/10', '03/10', '04/10'], $datos['etiquetas']);
        $this->assertSame([25.0, 0.0, 30.0, 0.0], $datos['valores']);
        $this->assertSame(55.0, $datos['total']);
    }

    public function test_por_defecto_el_panel_usa_el_mes_actual(): void
    {
        $this->actingAs($this->admin())->get(route('panel'))
            ->assertOk()
            ->assertViewHas('desde', fn ($d) => $d->toDateString() === '2026-10-01')
            ->assertViewHas('hasta', fn ($h) => $h->toDateString() === '2026-10-20')
            ->assertViewHas('lineas', fn ($l) => count($l['etiquetas']) === 20)
            ->assertSee('Ventas por día del 01/10/2026 al 20/10/2026');
    }

    public function test_barras_muestran_los_10_mas_vendidos_del_periodo(): void
    {
        foreach (range(1, 12) as $n) {
            $p = $this->producto(sprintf('T-%03d', $n), "Producto {$n}");
            $this->vender($p, $n, 2, '2026-10-10 10:00:00');                     // Producto 12 vende 12, el 1 vende 1
        }
        $viejo = $this->producto('T-099', 'Producto viejo');
        $this->vender($viejo, 500, 2, '2026-08-10 10:00:00');                    // fuera del periodo

        $filas = $this->actingAs($this->admin())->get(route('panel'))
            ->assertOk()
            ->assertSee('graficoVendidos')
            ->assertDontSee('Producto viejo')
            ->viewData('masVendidos');

        $this->assertCount(10, $filas);
        $this->assertSame('Producto 12', $filas[0]['nombre']);
        $this->assertSame(12, $filas[0]['unidades']);
        $this->assertSame('Producto 3', $filas[9]['nombre']);                    // el 1 y el 2 quedan fuera
    }

    public function test_el_filtro_cambia_el_periodo_de_los_graficos(): void
    {
        $p = $this->producto('F-001', 'Producto filtro');
        $this->vender($p, 4, 10, '2026-09-15 10:00:00');

        $this->actingAs($this->admin())
            ->get(route('panel', ['desde' => '2026-09-01', 'hasta' => '2026-09-30']))
            ->assertOk()
            ->assertSee('Producto filtro')
            ->assertViewHas('lineas', fn ($l) => count($l['etiquetas']) === 30 && $l['total'] === 40.0);
    }

    public function test_hay_tabla_alternativa_para_cada_grafico(): void
    {
        $p = $this->producto('A-001', 'Producto tabla');
        $this->vender($p, 1, 10, '2026-10-10 10:00:00');

        $this->actingAs($this->admin())->get(route('panel'))
            ->assertOk()
            ->assertSee('Ventas por día del periodo')
            ->assertSee('Productos más vendidos del periodo')
            ->assertSee('Ver los datos en tabla');
    }

    public function test_hasta_no_puede_ser_menor_que_desde(): void
    {
        $this->actingAs($this->admin())
            ->get(route('panel', ['desde' => '2026-10-20', 'hasta' => '2026-10-01']))
            ->assertSessionHasErrors('hasta');

        $this->actingAs($this->admin())
            ->get(route('panel', ['desde' => 'no-es-fecha']))
            ->assertSessionHasErrors('desde');
    }

    public function test_vendedor_recibe_403_aunque_envie_fechas(): void
    {
        $vendedor = Usuario::where('email', 'vendedor@microempresa.test')->first();

        $this->actingAs($vendedor)->get(route('panel', ['desde' => '2026-10-01']))->assertForbidden();
    }
}
