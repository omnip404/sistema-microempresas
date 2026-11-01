<?php

namespace Tests\Feature;

use App\Models\MovimientoInventario;
use App\Models\Producto;
use App\Models\Usuario;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventarioTest extends TestCase
{
    use RefreshDatabase;

    private Producto $arroz;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->arroz = Producto::where('codigo', 'ABA-001')->first();   // stock 20
    }

    private function admin(): Usuario
    {
        return Usuario::where('email', 'admin@microempresa.test')->first();
    }

    private function vendedor(): Usuario
    {
        return Usuario::where('email', 'vendedor@microempresa.test')->first();
    }

    public function test_ajuste_suma_unidades_y_crea_movimiento(): void
    {
        $this->actingAs($this->admin())
            ->post(route('inventario.ajustar', $this->arroz), ['operacion' => 'sumar', 'cantidad' => 5, 'observacion' => 'Conteo físico'])
            ->assertRedirect(route('inventario.movimientos', $this->arroz));

        $this->assertEquals(25, $this->arroz->fresh()->stock);
        $this->assertDatabaseHas('movimientos_inventario', [
            'producto_id' => $this->arroz->id, 'tipo' => 'AJUSTE', 'cantidad' => 5,
            'stock_resultante' => 25, 'observacion' => 'Conteo físico',
        ]);
    }

    public function test_ajuste_resta_unidades(): void
    {
        $this->actingAs($this->admin())
            ->post(route('inventario.ajustar', $this->arroz), ['operacion' => 'restar', 'cantidad' => 8, 'observacion' => 'Producto dañado']);

        $this->assertEquals(12, $this->arroz->fresh()->stock);
        $this->assertDatabaseHas('movimientos_inventario', ['tipo' => 'AJUSTE', 'cantidad' => -8, 'stock_resultante' => 12]);
    }

    public function test_el_stock_nunca_queda_negativo(): void
    {
        $this->actingAs($this->admin())
            ->post(route('inventario.ajustar', $this->arroz), ['operacion' => 'restar', 'cantidad' => 21, 'observacion' => 'Error de conteo'])
            ->assertSessionHasErrors('cantidad');

        $this->assertEquals(20, $this->arroz->fresh()->stock);
        $this->assertEquals(0, MovimientoInventario::where('tipo', 'AJUSTE')->count());
    }

    public function test_observacion_y_cantidad_son_obligatorias(): void
    {
        $this->actingAs($this->admin())
            ->post(route('inventario.ajustar', $this->arroz), ['operacion' => 'sumar', 'cantidad' => 0, 'observacion' => ''])
            ->assertSessionHasErrors(['cantidad', 'observacion']);

        $this->assertEquals(20, $this->arroz->fresh()->stock);
    }

    public function test_historial_muestra_movimientos_del_producto(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post(route('inventario.ajustar', $this->arroz), ['operacion' => 'sumar', 'cantidad' => 3, 'observacion' => 'Hallazgo en bodega']);

        $this->actingAs($admin)->get(route('inventario.movimientos', $this->arroz))
            ->assertOk()
            ->assertSee('Hallazgo en bodega')
            ->assertSee('AJUSTE-MANUAL')
            ->assertSee($admin->nombre);

        $this->actingAs($admin)->get(route('productos.index'))
            ->assertSee(route('inventario.movimientos', $this->arroz), false);
    }

    public function test_vendedor_recibe_403(): void
    {
        $vendedor = $this->vendedor();

        $this->actingAs($vendedor)->get(route('inventario.movimientos', $this->arroz))->assertForbidden();
        $this->actingAs($vendedor)->get(route('inventario.ajuste', $this->arroz))->assertForbidden();
        $this->actingAs($vendedor)
            ->post(route('inventario.ajustar', $this->arroz), ['operacion' => 'sumar', 'cantidad' => 1, 'observacion' => 'x'])
            ->assertForbidden();
        $this->assertEquals(20, $this->arroz->fresh()->stock);
    }
}
