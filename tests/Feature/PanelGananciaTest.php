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

class PanelGananciaTest extends TestCase
{
    use RefreshDatabase;

    private Producto $a;

    private Producto $b;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        Carbon::setTestNow('2026-10-20 15:00:00');

        Producto::query()->update(['activo' => false]);
        $categoria = Categoria::first();
        $this->a = $this->producto($categoria, 'G-001', 'Arroz ganancia', compra: 4.50);
        $this->b = $this->producto($categoria, 'G-002', 'Aceite ganancia', compra: 12.00);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function producto(Categoria $categoria, string $codigo, string $nombre, float $compra): Producto
    {
        return Producto::create([
            'categoria_id' => $categoria->id, 'codigo' => $codigo, 'nombre' => $nombre, 'unidad_medida' => 'unidad',
            'precio_compra' => $compra, 'precio_venta' => $compra * 2, 'stock' => 100, 'stock_minimo' => 1, 'activo' => true,
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

    private function datos(): void
    {
        $this->vender($this->a, 10, 8.00, '2026-10-05 10:00:00');                          // 10 × (8 − 4,50) = 35
        $this->vender($this->b, 2, 15.50, '2026-10-18 12:00:00');                          // 2 × (15,50 − 12) = 7
        $this->vender($this->a, 50, 8.00, '2026-10-10 10:00:00', Venta::ANULADA);          // no cuenta
        $this->vender($this->a, 100, 8.00, '2026-09-30 10:00:00');                         // mes anterior: no cuenta
    }

    public function test_ganancia_estimada_del_mes_con_valores_conocidos(): void
    {
        $this->datos();

        $ganancia = app(ReporteService::class)->gananciaEstimada(now()->startOfMonth(), now());

        $this->assertEquals(42.00, $ganancia);   // 35 + 7
    }

    public function test_ganancia_negativa_si_se_vende_bajo_el_costo(): void
    {
        $this->vender($this->b, 3, 10.00, '2026-10-02 09:00:00');   // 3 × (10 − 12) = −6

        $this->assertEquals(-6.00, app(ReporteService::class)->gananciaEstimada(now()->startOfMonth(), now()));
    }

    public function test_sin_ventas_la_ganancia_es_cero(): void
    {
        $this->assertSame(0.0, app(ReporteService::class)->gananciaEstimada(now()->startOfMonth(), now()));
    }

    public function test_el_panel_muestra_la_ganancia_como_estimada(): void
    {
        $this->datos();

        $this->actingAs($this->admin())->get(route('panel'))
            ->assertOk()
            ->assertSee('Ganancia estimada del mes')
            ->assertSee('Bs 42,00')
            ->assertSee('Estimada:');
    }

    public function test_el_panel_sigue_mostrando_ventas_del_mes_y_de_hoy(): void
    {
        $this->vender($this->a, 1, 8.00, '2026-10-20 09:00:00');
        $this->vender($this->a, 2, 8.00, '2026-10-03 09:00:00');

        $this->actingAs($this->admin())->get(route('panel'))
            ->assertOk()
            ->assertSeeInOrder(['Ventas de hoy', 'Bs 8,00', 'Ventas del mes', 'Bs 24,00']);
    }

    public function test_vendedor_no_accede_al_panel(): void
    {
        $vendedor = Usuario::where('email', 'vendedor@microempresa.test')->first();

        $this->actingAs($vendedor)->get(route('panel'))->assertForbidden();
    }
}
