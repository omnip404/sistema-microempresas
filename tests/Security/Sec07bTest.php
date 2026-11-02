<?php

namespace Tests\Security;

use App\Models\Compra;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\Usuario;
use App\Services\CompraService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Sec07b: dos anulaciones concurrentes de la misma compra no deben descontar
 * el stock dos veces. Se simula la intercalación: la petición B cargó la compra
 * (REGISTRADA) antes de que la petición A la anulara y luego entra al servicio.
 */
class Sec07bTest extends TestCase
{
    use RefreshDatabase;

    public function test_anular_con_instancia_desactualizada_no_descuenta_stock_dos_veces(): void
    {
        $this->seed(DatabaseSeeder::class);
        $servicio = new CompraService;
        $admin = Usuario::where('email', 'admin@microempresa.test')->first();
        $proveedor = Proveedor::create(['nombre' => 'Distribuidora Andina']);
        $arroz = Producto::where('codigo', 'ABA-001')->first(); // stock 20

        $compra = $servicio->registrar($admin, $proveedor->id, [
            ['producto_id' => $arroz->id, 'cantidad' => 10, 'costo_unitario' => 40],
        ], null); // stock 30

        // Petición B: route model binding carga la compra todavía REGISTRADA
        $instanciaVieja = Compra::find($compra->id);
        $instanciaVieja->load('detalles');

        // Petición A: anula primero
        $servicio->anular(Compra::find($compra->id), $admin); // stock 20

        // Petición B continúa con su instancia vieja
        try {
            $servicio->anular($instanciaVieja, $admin);
        } catch (ValidationException $e) {
            // comportamiento seguro esperado
        }

        $this->assertEquals(20, $arroz->fresh()->stock, 'El stock se descontó dos veces');
        $this->assertEquals(1, \DB::table('movimientos_inventario')
            ->where('referencia', "ANULA-COMPRA-{$compra->id}")->count());
    }
}
