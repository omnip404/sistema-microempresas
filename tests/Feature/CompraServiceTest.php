<?php

namespace Tests\Feature;

use App\Models\Compra;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\Usuario;
use App\Services\CompraService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CompraServiceTest extends TestCase
{
    use RefreshDatabase;

    private CompraService $servicio;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->servicio = new CompraService;
    }

    private function admin(): Usuario
    {
        return Usuario::where('email', 'admin@microempresa.test')->first();
    }

    private function proveedor(): Proveedor
    {
        return Proveedor::create(['nombre' => 'Distribuidora Andina']);
    }

    public function test_registrar_suma_stock_actualiza_costo_y_crea_movimiento(): void
    {
        $arroz = Producto::where('codigo', 'ABA-001')->first();    // stock 20
        $aceite = Producto::where('codigo', 'ABA-002')->first();   // stock 30
        $admin = $this->admin();

        $compra = $this->servicio->registrar($admin, $this->proveedor()->id, [
            ['producto_id' => $arroz->id, 'cantidad' => 10, 'costo_unitario' => 40],
            ['producto_id' => $aceite->id, 'cantidad' => 5, 'costo_unitario' => 12.5],
        ], 'FAC-001');

        $this->assertEquals(462.50, (float) $compra->total);   // 10×40 + 5×12,5
        $this->assertEquals(Compra::REGISTRADA, $compra->estado);
        $this->assertEquals('FAC-001', $compra->nro_comprobante);
        $this->assertCount(2, $compra->detalles);

        $this->assertEquals(30, $arroz->fresh()->stock);
        $this->assertEquals(35, $aceite->fresh()->stock);
        $this->assertEquals(40.00, (float) $arroz->fresh()->precio_compra);
        $this->assertEquals(12.50, (float) $aceite->fresh()->precio_compra);

        $this->assertDatabaseHas('movimientos_inventario', [
            'producto_id' => $arroz->id, 'tipo' => 'ENTRADA', 'cantidad' => 10,
            'stock_resultante' => 30, 'referencia' => "COMPRA-{$compra->id}",
        ]);
        $this->assertDatabaseHas('bitacora', ['tabla' => 'compras', 'accion' => 'CREAR']);
    }

    public function test_registrar_rechaza_cantidad_o_costo_invalidos_sin_guardar_nada(): void
    {
        $arroz = Producto::where('codigo', 'ABA-001')->first();
        $proveedor = $this->proveedor();

        foreach ([
            [['producto_id' => $arroz->id, 'cantidad' => 0, 'costo_unitario' => 10]],
            [['producto_id' => $arroz->id, 'cantidad' => 2, 'costo_unitario' => -1]],
            [],
        ] as $items) {
            try {
                $this->servicio->registrar($this->admin(), $proveedor->id, $items, null);
                $this->fail('Debía lanzar ValidationException');
            } catch (ValidationException $e) {
                $this->assertArrayHasKey('items', $e->errors());
            }
        }

        $this->assertEquals(0, Compra::count());
        $this->assertEquals(20, $arroz->fresh()->stock);
    }

    public function test_anular_descuenta_stock_y_crea_movimiento_de_salida(): void
    {
        $arroz = Producto::where('codigo', 'ABA-001')->first();
        $admin = $this->admin();
        $compra = $this->servicio->registrar($admin, $this->proveedor()->id, [
            ['producto_id' => $arroz->id, 'cantidad' => 10, 'costo_unitario' => 40],
        ], null);

        $this->servicio->anular($compra, $admin);

        $this->assertEquals(20, $arroz->fresh()->stock);
        $this->assertEquals(Compra::ANULADA, $compra->fresh()->estado);
        $this->assertDatabaseHas('movimientos_inventario', [
            'producto_id' => $arroz->id, 'tipo' => 'SALIDA', 'cantidad' => -10,
            'stock_resultante' => 20, 'referencia' => "ANULA-COMPRA-{$compra->id}",
        ]);
    }

    public function test_anular_falla_si_ya_no_hay_stock_suficiente_para_revertir(): void
    {
        $arroz = Producto::where('codigo', 'ABA-001')->first();
        $aceite = Producto::where('codigo', 'ABA-002')->first();
        $admin = $this->admin();
        $compra = $this->servicio->registrar($admin, $this->proveedor()->id, [
            ['producto_id' => $aceite->id, 'cantidad' => 5, 'costo_unitario' => 12],
            ['producto_id' => $arroz->id, 'cantidad' => 10, 'costo_unitario' => 40],
        ], null);

        // Se vendió casi todo el arroz después de la compra: quedan 3 y la compra ingresó 10
        $arroz->update(['stock' => 3]);

        try {
            $this->servicio->anular($compra, $admin);
            $this->fail('Debía lanzar ValidationException');
        } catch (ValidationException $e) {
            $this->assertStringContainsString($arroz->nombre, $e->errors()['compra'][0]);
        }

        // Nada cambió: ni el estado ni el stock de los otros productos
        $this->assertEquals(Compra::REGISTRADA, $compra->fresh()->estado);
        $this->assertEquals(3, $arroz->fresh()->stock);
        $this->assertEquals(35, $aceite->fresh()->stock);
        $this->assertDatabaseMissing('movimientos_inventario', ['referencia' => "ANULA-COMPRA-{$compra->id}"]);
    }

    public function test_no_se_anula_dos_veces(): void
    {
        $arroz = Producto::where('codigo', 'ABA-001')->first();
        $admin = $this->admin();
        $compra = $this->servicio->registrar($admin, $this->proveedor()->id, [
            ['producto_id' => $arroz->id, 'cantidad' => 4, 'costo_unitario' => 40],
        ], null);
        $this->servicio->anular($compra, $admin);

        $this->expectException(ValidationException::class);
        $this->servicio->anular($compra->fresh(), $admin);
    }
}
