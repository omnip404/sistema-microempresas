<?php

namespace Tests\Security;

use App\Models\MovimientoInventario;
use App\Models\Producto;
use App\Models\Usuario;
use App\Models\Venta;
use App\Services\VentaService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Sec07a: dos anulaciones simultaneas de la misma venta no deben devolver el stock dos veces.
 * Simula la intercalacion: la segunda peticion ya cargo la venta (REGISTRADA) por route binding
 * antes de que la primera la anule.
 */
class Sec07aTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_anular_con_instancia_vieja_no_devuelve_stock_dos_veces(): void
    {
        $vendedor = Usuario::where('email', 'vendedor@microempresa.test')->first();
        $admin = Usuario::where('email', 'admin@microempresa.test')->first();
        $arroz = Producto::where('codigo', 'ABA-001')->first(); // stock 20

        $this->actingAs($vendedor)->post('/ventas', ['items' => [['producto_id' => $arroz->id, 'cantidad' => 2]]]);
        $venta = Venta::first();
        $this->assertEquals(18, $arroz->fresh()->stock);

        // Peticion B: el binding de ruta ya cargo la venta con estado REGISTRADA.
        $ventaVieja = Venta::find($venta->id);

        // Peticion A: anula la venta completa.
        $this->actingAs($admin)
            ->post("/ventas/{$venta->id}/anular", ['motivo' => 'Primera'])
            ->assertSessionHasNoErrors();
        $this->assertEquals(20, $arroz->fresh()->stock);

        // Peticion B continua con su instancia vieja.
        try {
            app(VentaService::class)->anular($ventaVieja, 'Segunda', $admin);
        } catch (ValidationException) {
            // comportamiento seguro esperado
        }

        $this->assertEquals(20, $arroz->fresh()->stock, 'El stock se devolvio dos veces');
        $this->assertEquals(1, MovimientoInventario::where('referencia', "ANULA-VENTA-{$venta->id}")->count());
    }
}
