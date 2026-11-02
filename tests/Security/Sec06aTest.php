<?php

namespace Tests\Security;

use App\Models\Cliente;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\Usuario;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Sec06a: las referencias (cliente_id, proveedor_id, items.*.producto_id) enviadas
 * como arreglo deben rechazarse con un error de validacion (302 + errores),
 * nunca con un error 500.
 */
class Sec06aTest extends TestCase
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

    public function test_venta_con_cliente_id_arreglo_se_rechaza_por_validacion(): void
    {
        $cliente = Cliente::first();
        $producto = Producto::where('codigo', 'ABA-001')->first();

        $this->actingAs($this->usuario('vendedor@microempresa.test'))
            ->post('/ventas', [
                'cliente_id' => [$cliente->id],
                'items' => [['producto_id' => $producto->id, 'cantidad' => 1]],
            ])
            ->assertStatus(302)
            ->assertSessionHasErrors('cliente_id');
    }

    public function test_venta_con_producto_id_arreglo_repetido_se_rechaza_por_validacion(): void
    {
        $producto = Producto::where('codigo', 'ABA-001')->first();

        $this->actingAs($this->usuario('vendedor@microempresa.test'))
            ->post('/ventas', [
                'items' => [
                    ['producto_id' => [$producto->id], 'cantidad' => 1],
                    ['producto_id' => [$producto->id], 'cantidad' => 1],
                ],
            ])
            ->assertStatus(302)
            ->assertSessionHasErrors();
    }

    public function test_compra_con_proveedor_id_arreglo_se_rechaza_por_validacion(): void
    {
        $proveedor = Proveedor::first();
        $producto = Producto::where('codigo', 'ABA-001')->first();

        $this->actingAs($this->usuario('admin@microempresa.test'))
            ->post('/compras', [
                'proveedor_id' => [$proveedor->id],
                'items' => [['producto_id' => $producto->id, 'cantidad' => 1, 'costo_unitario' => 10]],
            ])
            ->assertStatus(302)
            ->assertSessionHasErrors('proveedor_id');
    }

    public function test_compra_con_producto_id_arreglo_repetido_se_rechaza_por_validacion(): void
    {
        $proveedor = Proveedor::first();
        $producto = Producto::where('codigo', 'ABA-001')->first();

        $this->actingAs($this->usuario('admin@microempresa.test'))
            ->post('/compras', [
                'proveedor_id' => $proveedor->id,
                'items' => [
                    ['producto_id' => [$producto->id], 'cantidad' => 1, 'costo_unitario' => 10],
                    ['producto_id' => [$producto->id], 'cantidad' => 1, 'costo_unitario' => 10],
                ],
            ])
            ->assertStatus(302)
            ->assertSessionHasErrors();
    }
}
