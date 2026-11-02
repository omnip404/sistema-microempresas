<?php

namespace Tests\Feature;

use App\Models\Compra;
use App\Models\DetalleCompra;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\Usuario;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReporteComprasTest extends TestCase
{
    use RefreshDatabase;

    private Proveedor $andina;

    private Proveedor $nacional;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);

        $this->andina = Proveedor::create(['nombre' => 'Distribuidora Andina']);
        $this->nacional = Proveedor::create(['nombre' => 'Importadora Nacional']);

        $this->compra($this->andina, '2026-10-05 10:00:00', 10, 4.50, 'F-001');            // 45
        $this->compra($this->andina, '2026-10-20 15:00:00', 5, 8.00, 'F-002');             // 40
        $this->compra($this->nacional, '2026-10-12 09:00:00', 20, 2.00, 'F-003');          // 40
        $this->compra($this->andina, '2026-10-15 09:00:00', 100, 9.00, 'F-ANULADA', Compra::ANULADA);
        $this->compra($this->andina, '2026-09-01 09:00:00', 7, 1.00, 'F-FUERA');           // fuera del periodo
    }

    private function admin(): Usuario
    {
        return Usuario::where('email', 'admin@microempresa.test')->first();
    }

    private function compra(Proveedor $proveedor, string $fecha, int $cantidad, float $costo, string $nro, string $estado = Compra::REGISTRADA): void
    {
        $compra = Compra::create([
            'usuario_id' => $this->admin()->id, 'proveedor_id' => $proveedor->id, 'fecha' => $fecha,
            'nro_comprobante' => $nro, 'total' => $cantidad * $costo, 'estado' => $estado,
        ]);
        DetalleCompra::create([
            'compra_id' => $compra->id, 'producto_id' => Producto::first()->id,
            'cantidad' => $cantidad, 'costo_unitario' => $costo,
        ]);
    }

    private function periodo(array $extra = []): array
    {
        return ['tipo' => 'compras', 'desde' => '2026-10-01', 'hasta' => '2026-10-31'] + $extra;
    }

    public function test_reporte_de_compras_suma_solo_registradas_del_periodo(): void
    {
        $reporte = $this->actingAs($this->admin())->get(route('reportes.index', $this->periodo()))
            ->assertOk()
            ->assertSee('Compras por periodo')
            ->assertSee('Distribuidora Andina')
            ->assertDontSee('F-ANULADA')
            ->assertDontSee('F-FUERA')
            ->viewData('reporte');

        $this->assertSame(['F-001', 'F-003', 'F-002'], $reporte['filas']->pluck('nro_comprobante')->all());
        $this->assertEquals(['unidades' => 35, 'total' => 125.0], $reporte['totales']);   // 45 + 40 + 40
    }

    public function test_filtra_por_proveedor(): void
    {
        $reporte = $this->actingAs($this->admin())
            ->get(route('reportes.index', $this->periodo(['proveedor_id' => $this->andina->id])))
            ->assertOk()
            ->viewData('reporte');

        $this->assertSame(['F-001', 'F-002'], $reporte['filas']->pluck('nro_comprobante')->all());
        $this->assertEquals(['unidades' => 15, 'total' => 85.0], $reporte['totales']);
    }

    public function test_proveedor_inexistente_o_fechas_invertidas_fallan_la_validacion(): void
    {
        $this->actingAs($this->admin())
            ->get(route('reportes.index', $this->periodo(['proveedor_id' => 9999])))
            ->assertSessionHasErrors('proveedor_id');

        $this->actingAs($this->admin())
            ->get(route('reportes.index', ['tipo' => 'compras', 'desde' => '2026-10-31', 'hasta' => '2026-10-01']))
            ->assertSessionHasErrors('hasta');
    }

    public function test_csv_de_compras_respeta_el_filtro_y_el_formato_de_excel(): void
    {
        $contenido = $this->actingAs($this->admin())
            ->get(route('reportes.exportar', $this->periodo(['proveedor_id' => $this->nacional->id])))
            ->assertOk()
            ->streamedContent();

        $this->assertStringStartsWith("\xEF\xBB\xBF", $contenido);
        $lineas = preg_split('/\r?\n/', trim(substr($contenido, 3)));

        $this->assertSame('Fecha;"N.º de comprobante";Proveedor;Unidades;"Total (Bs)"', $lineas[0]);
        $this->assertSame('12/10/2026;F-003;"Importadora Nacional";20;40,00', $lineas[1]);
        $this->assertSame('Total;;;20;40,00', $lineas[2]);
        $this->assertCount(3, $lineas);
    }

    public function test_periodo_sin_compras_muestra_mensaje(): void
    {
        $this->actingAs($this->admin())
            ->get(route('reportes.index', ['tipo' => 'compras', 'desde' => '2025-01-01', 'hasta' => '2025-01-31']))
            ->assertOk()
            ->assertSee('No hay compras registradas en este periodo.');
    }

    public function test_vendedor_recibe_403(): void
    {
        $vendedor = Usuario::where('email', 'vendedor@microempresa.test')->first();

        $this->actingAs($vendedor)->get(route('reportes.index', $this->periodo()))->assertForbidden();
        $this->actingAs($vendedor)->get(route('reportes.exportar', $this->periodo()))->assertForbidden();
    }
}
