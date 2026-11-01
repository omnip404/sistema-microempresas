<?php

namespace Tests\Feature;

use App\Models\Usuario;
use App\Models\Venta;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComparacionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    private function admin(): Usuario
    {
        return Usuario::where('email', 'admin@microempresa.test')->first();
    }

    private function venta(string $fecha, float $total, string $estado = Venta::REGISTRADA): void
    {
        Venta::create(['usuario_id' => $this->admin()->id, 'fecha' => $fecha, 'total' => $total, 'estado' => $estado]);
    }

    private function consultar(array $parametros)
    {
        return $this->actingAs($this->admin())->get(route('comparacion.index', $parametros));
    }

    private const MARZO = ['desde_a' => '2026-03-01', 'hasta_a' => '2026-03-31'];

    private const FEBRERO = ['desde_b' => '2026-02-01', 'hasta_b' => '2026-02-28'];

    public function test_compara_total_cantidad_promedio_y_variacion(): void
    {
        // Marzo (A): 3 ventas por 450 → promedio 150. Febrero (B): 2 ventas por 300 → promedio 150.
        $this->venta('2026-03-02 10:00:00', 100);
        $this->venta('2026-03-15 10:00:00', 200);
        $this->venta('2026-03-30 10:00:00', 150);
        $this->venta('2026-03-10 10:00:00', 999, Venta::ANULADA);   // las anuladas no cuentan
        $this->venta('2026-02-10 10:00:00', 100);
        $this->venta('2026-02-20 10:00:00', 200);

        $respuesta = $this->consultar(self::MARZO + self::FEBRERO)->assertOk();
        $comparacion = $respuesta->viewData('comparacion');

        $this->assertEquals(['total' => 450.0, 'ventas' => 3, 'promedio' => 150.0], $comparacion['a']);
        $this->assertEquals(['total' => 300.0, 'ventas' => 2, 'promedio' => 150.0], $comparacion['b']);
        $this->assertEquals(['total' => 50.0, 'ventas' => 50.0, 'promedio' => 0.0], $comparacion['variacion']);

        $respuesta->assertSee('Subió 50,0 %')->assertSee('Sin cambio (0,0 %)')->assertSee('450,00');
    }

    public function test_variacion_negativa_cuando_el_periodo_a_vendio_menos(): void
    {
        $this->venta('2026-03-02 10:00:00', 75);
        $this->venta('2026-02-10 10:00:00', 100);

        $this->consultar(self::MARZO + self::FEBRERO)
            ->assertSee('Bajó 25,0 %')
            ->assertViewHas('comparacion', fn ($c) => $c['variacion']['total'] === -25.0);
    }

    public function test_si_el_periodo_b_esta_vacio_no_divide_y_avisa(): void
    {
        $this->venta('2026-03-02 10:00:00', 500);

        $respuesta = $this->consultar(self::MARZO + self::FEBRERO)
            ->assertOk()
            ->assertSee('sin datos del periodo anterior')
            ->assertDontSee('Subió');

        $this->assertEquals(['total' => null, 'ventas' => null, 'promedio' => null], $respuesta->viewData('comparacion')['variacion']);
        $this->assertEquals(['total' => 0.0, 'ventas' => 0, 'promedio' => 0.0], $respuesta->viewData('comparacion')['b']);
    }

    public function test_si_ambos_periodos_estan_vacios_no_falla(): void
    {
        $this->consultar(self::MARZO + self::FEBRERO)
            ->assertOk()
            ->assertSee('sin datos del periodo anterior');
    }

    public function test_si_el_periodo_a_esta_vacio_la_variacion_es_menos_100(): void
    {
        $this->venta('2026-02-10 10:00:00', 100);

        $this->consultar(self::MARZO + self::FEBRERO)
            ->assertSee('Bajó 100,0 %');
    }

    public function test_por_defecto_compara_el_mes_actual_con_el_anterior(): void
    {
        $this->consultar([])
            ->assertOk()
            ->assertViewHas('desdeA', fn ($d) => $d->toDateString() === now()->startOfMonth()->toDateString())
            ->assertViewHas('hastaA', fn ($h) => $h->toDateString() === now()->toDateString())
            ->assertViewHas('desdeB', fn ($d) => $d->toDateString() === now()->subMonthNoOverflow()->startOfMonth()->toDateString())
            ->assertViewHas('hastaB', fn ($h) => $h->toDateString() === now()->subMonthNoOverflow()->endOfMonth()->toDateString());
    }

    public function test_validacion_de_fechas_de_ambos_periodos(): void
    {
        $this->consultar(['desde_a' => '2026-03-10', 'hasta_a' => '2026-03-01'])->assertSessionHasErrors('hasta_a');
        $this->consultar(['desde_b' => '2026-02-10', 'hasta_b' => '2026-02-01'])->assertSessionHasErrors('hasta_b');
        $this->consultar(['desde_a' => 'no-es-fecha'])->assertSessionHasErrors('desde_a');
    }

    public function test_pantalla_incluye_tabla_accesible_y_grafico(): void
    {
        $this->consultar(self::MARZO + self::FEBRERO)
            ->assertSee('<caption class="visually-hidden">', false)
            ->assertSee('id="graficoComparacion"', false)
            ->assertSee('role="img"', false)
            ->assertSee("color('--grafico')", false);
    }

    public function test_vendedor_recibe_403(): void
    {
        $vendedor = Usuario::where('email', 'vendedor@microempresa.test')->first();

        $this->actingAs($vendedor)->get(route('comparacion.index'))->assertForbidden();
    }
}
