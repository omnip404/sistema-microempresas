<?php

namespace Tests\Feature;

use App\Models\Compra;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\Usuario;
use App\Models\Venta;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Mantenimiento correctivo: los productos dados de baja no se pueden vender ni comprar. */
class MantenimientoCorrectivoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    private function usuario(string $email): Usuario
    {
        return Usuario::where('email', $email)->first();
    }

    private function productoDadoDeBaja(): Producto
    {
        $producto = Producto::where('codigo', 'ABA-001')->first();   // stock 20
        $producto->update(['activo' => false]);

        return $producto;
    }

    public function test_no_permite_vender_un_producto_dado_de_baja(): void
    {
        $producto = $this->productoDadoDeBaja();

        $this->actingAs($this->usuario('vendedor@microempresa.test'))
            ->post('/ventas', ['items' => [['producto_id' => $producto->id, 'cantidad' => 2]]])
            ->assertSessionHasErrors('items');

        $this->assertEquals(20, $producto->fresh()->stock);
        $this->assertDatabaseCount('ventas', 0);
        $this->assertDatabaseMissing('movimientos_inventario', ['producto_id' => $producto->id, 'tipo' => 'SALIDA']);
    }

    public function test_no_permite_comprar_un_producto_dado_de_baja(): void
    {
        $producto = $this->productoDadoDeBaja();
        $proveedor = Proveedor::create(['nombre' => 'Distribuidora Andina']);

        $this->actingAs($this->usuario('admin@microempresa.test'))
            ->post(route('compras.store'), [
                'proveedor_id' => $proveedor->id,
                'items' => [['producto_id' => $producto->id, 'cantidad' => 5, 'costo_unitario' => 40]],
            ])
            ->assertSessionHasErrors('items');

        $this->assertEquals(20, $producto->fresh()->stock);
        $this->assertEquals(0, Compra::count());
    }

    public function test_un_producto_activo_se_sigue_vendiendo_normalmente(): void
    {
        $producto = Producto::where('codigo', 'ABA-001')->first();

        $this->actingAs($this->usuario('vendedor@microempresa.test'))
            ->post('/ventas', ['items' => [['producto_id' => $producto->id, 'cantidad' => 2]]])
            ->assertSessionDoesntHaveErrors();

        $this->assertEquals(18, $producto->fresh()->stock);
        $this->assertEquals(1, Venta::count());
    }
}
