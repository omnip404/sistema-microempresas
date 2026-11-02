<?php

namespace Tests\Security;

use App\Models\Producto;
use App\Models\Usuario;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Baja lógica no respetada en el servidor: VentaController::create oculta los productos con
 * activo=false, pero store (exists:productos,id) y VentaService::registrar los aceptan.
 * El comportamiento seguro es rechazar la venta y no tocar el stock.
 */
class Sec06bTest extends TestCase
{
    use RefreshDatabase;

    public function test_vendedor_no_puede_vender_un_producto_dado_de_baja(): void
    {
        $this->seed(DatabaseSeeder::class);

        $vendedor = Usuario::where('email', 'vendedor@microempresa.test')->first();
        $admin = Usuario::where('email', 'admin@microempresa.test')->first();
        $arroz = Producto::where('codigo', 'ABA-001')->first();   // stock 20

        // El Administrador da de baja el producto (DELETE /productos/{p} → activo=false).
        $this->actingAs($admin)->delete("/productos/{$arroz->id}");
        $this->assertFalse($arroz->fresh()->activo);

        $this->actingAs($vendedor)
            ->post('/ventas', ['items' => [['producto_id' => $arroz->id, 'cantidad' => 2]]])
            ->assertSessionHasErrors();

        $this->assertDatabaseCount('ventas', 0);
        $this->assertEquals(20, $arroz->fresh()->stock);
    }
}
