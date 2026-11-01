<?php

namespace Tests\Feature;

use App\Models\Producto;
use App\Models\Usuario;
use App\Models\Venta;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VentaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    private function vendedor(): Usuario
    {
        return Usuario::where('email', 'vendedor@microempresa.test')->first();
    }

    private function admin(): Usuario
    {
        return Usuario::where('email', 'admin@microempresa.test')->first();
    }

    public function test_registrar_venta_descuenta_stock_y_calcula_total(): void
    {
        $arroz = Producto::where('codigo', 'ABA-001')->first();   // stock 20, Bs 55
        $aceite = Producto::where('codigo', 'ABA-002')->first();  // stock 30, Bs 17

        $this->actingAs($this->vendedor())->post('/ventas', ['items' => [
            ['producto_id' => $arroz->id, 'cantidad' => 2],
            ['producto_id' => $aceite->id, 'cantidad' => 3],
        ]])->assertRedirect(route('ventas.create'));

        $venta = Venta::first();
        $this->assertEquals(161.00, (float) $venta->total);          // 2×55 + 3×17
        $this->assertEquals(18, $arroz->fresh()->stock);
        $this->assertEquals(27, $aceite->fresh()->stock);
        $this->assertDatabaseHas('movimientos_inventario', ['producto_id' => $arroz->id, 'tipo' => 'SALIDA', 'stock_resultante' => 18]);
        $this->assertDatabaseHas('bitacora', ['tabla' => 'ventas', 'accion' => 'CREAR']);
    }

    public function test_no_permite_vender_mas_que_el_stock(): void
    {
        $leche = Producto::where('codigo', 'LAC-001')->first();   // stock 4

        $this->actingAs($this->vendedor())
            ->post('/ventas', ['items' => [['producto_id' => $leche->id, 'cantidad' => 5]]])
            ->assertSessionHasErrors('items');

        $this->assertEquals(4, $leche->fresh()->stock);
        $this->assertDatabaseCount('ventas', 0);                   // la transacción se revirtió
    }

    public function test_anular_venta_devuelve_stock(): void
    {
        $arroz = Producto::where('codigo', 'ABA-001')->first();
        $this->actingAs($this->vendedor())->post('/ventas', ['items' => [['producto_id' => $arroz->id, 'cantidad' => 2]]]);
        $venta = Venta::first();

        $this->actingAs($this->admin())
            ->post("/ventas/{$venta->id}/anular", ['motivo' => 'Error de registro'])
            ->assertSessionHasNoErrors();

        $this->assertEquals(Venta::ANULADA, $venta->fresh()->estado);
        $this->assertEquals(20, $arroz->fresh()->stock);
    }

    public function test_vendedor_no_accede_al_panel_ni_a_productos(): void
    {
        $this->actingAs($this->vendedor())->get('/panel')->assertForbidden();
        $this->actingAs($this->vendedor())->get('/productos')->assertForbidden();
        $this->actingAs($this->admin())->get('/panel')->assertOk()->assertSee('Stock bajo');
    }
}
