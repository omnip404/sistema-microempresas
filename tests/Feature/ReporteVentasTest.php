<?php

namespace Tests\Feature;

use App\Models\DetalleVenta;
use App\Models\Producto;
use App\Models\Usuario;
use App\Models\Venta;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReporteVentasTest extends TestCase
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

    private function vendedor(): Usuario
    {
        return Usuario::where('email', 'vendedor@microempresa.test')->first();
    }

    private function venta(string $fecha, float $total, int $unidades, string $estado = Venta::REGISTRADA): Venta
    {
        $venta = Venta::create([
            'usuario_id' => $this->vendedor()->id, 'fecha' => $fecha, 'total' => $total, 'estado' => $estado,
        ]);
        DetalleVenta::create([
            'venta_id' => $venta->id, 'producto_id' => Producto::first()->id,
            'cantidad' => $unidades, 'precio_unitario' => $total / $unidades,
        ]);

        return $venta;
    }

    private function datosDePrueba(): void
    {
        $this->venta('2026-03-05 10:00:00', 100.50, 3);
        $this->venta('2026-03-05 16:30:00', 49.50, 1);
        $this->venta('2026-03-07 09:00:00', 1250.00, 10);
        $this->venta('2026-03-06 09:00:00', 999.00, 9, Venta::ANULADA);   // no cuenta
        $this->venta('2026-04-02 09:00:00', 70.00, 2);                    // fuera del periodo
    }

    public function test_totales_por_dia_solo_con_ventas_registradas(): void
    {
        $this->datosDePrueba();

        $respuesta = $this->actingAs($this->admin())
            ->get(route('reportes.index', ['desde' => '2026-03-01', 'hasta' => '2026-03-31']))
            ->assertOk()
            ->assertSee('05/03/2026')
            ->assertSee('07/03/2026')
            ->assertDontSee('06/03/2026')
            ->assertDontSee('02/04/2026');

        $reporte = $respuesta->viewData('reporte');
        $this->assertCount(2, $reporte['filas']);
        $this->assertEquals(['fecha' => '2026-03-05', 'ventas' => 2, 'unidades' => 4, 'total' => 150.0], $reporte['filas'][0]);
        $this->assertEquals(['ventas' => 3, 'unidades' => 14, 'total' => 1400.0], $reporte['totales']);
        $respuesta->assertSee('1.400,00');
    }

    public function test_por_defecto_muestra_el_mes_actual(): void
    {
        $this->actingAs($this->admin())->get(route('reportes.index'))
            ->assertOk()
            ->assertSee(now()->startOfMonth()->format('d/m/Y'))
            ->assertViewHas('desde', fn ($d) => $d->toDateString() === now()->startOfMonth()->toDateString())
            ->assertViewHas('hasta', fn ($h) => $h->toDateString() === now()->toDateString());
    }

    public function test_hasta_no_puede_ser_menor_que_desde(): void
    {
        $this->actingAs($this->admin())
            ->get(route('reportes.index', ['desde' => '2026-03-10', 'hasta' => '2026-03-01']))
            ->assertSessionHasErrors('hasta');

        $this->actingAs($this->admin())
            ->get(route('reportes.exportar', ['desde' => '2026-03-10', 'hasta' => '2026-03-01']))
            ->assertSessionHasErrors('hasta');

        $this->actingAs($this->admin())
            ->get(route('reportes.index', ['desde' => 'no-es-fecha']))
            ->assertSessionHasErrors('desde');
    }

    public function test_csv_con_bom_separador_punto_y_coma_y_coma_decimal(): void
    {
        $this->datosDePrueba();

        $respuesta = $this->actingAs($this->admin())
            ->get(route('reportes.exportar', ['desde' => '2026-03-01', 'hasta' => '2026-03-31']))
            ->assertOk()
            ->assertDownload('ventas-2026-03-01_2026-03-31.csv');

        $contenido = $respuesta->streamedContent();

        $this->assertStringStartsWith("\xEF\xBB\xBF", $contenido);
        $lineas = preg_split('/\r?\n/', trim(substr($contenido, 3)));
        $this->assertEquals('Fecha;"N.º de ventas";Unidades;"Total (Bs)"', $lineas[0]);
        $this->assertEquals('05/03/2026;2;4;150,00', $lineas[1]);
        $this->assertEquals('07/03/2026;1;10;1250,00', $lineas[2]);
        $this->assertEquals('Total;3;14;1400,00', $lineas[3]);
        $this->assertCount(4, $lineas);
    }

    public function test_periodo_sin_ventas_muestra_mensaje_y_totales_en_cero(): void
    {
        $this->actingAs($this->admin())
            ->get(route('reportes.index', ['desde' => '2020-01-01', 'hasta' => '2020-01-31']))
            ->assertOk()
            ->assertSee('No hay ventas registradas en este periodo.');
    }

    public function test_pantalla_ofrece_exportar_e_imprimir_y_vendedor_recibe_403(): void
    {
        $this->actingAs($this->admin())->get(route('reportes.index'))
            ->assertSee('Exportar a Excel (CSV)')
            ->assertSee('Imprimir o guardar PDF');

        $vendedor = $this->vendedor();
        $this->actingAs($vendedor)->get(route('reportes.index'))->assertForbidden();
        $this->actingAs($vendedor)->get(route('reportes.exportar'))->assertForbidden();
    }
}
