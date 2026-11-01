<?php

namespace Tests\Feature;

use App\Models\Compra;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\Usuario;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompraTest extends TestCase
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

    public function test_administrador_registra_compra_desde_la_pantalla(): void
    {
        $arroz = Producto::where('codigo', 'ABA-001')->first();   // stock 20
        $proveedor = Proveedor::create(['nombre' => 'Distribuidora Andina']);

        $this->actingAs($this->admin())->get(route('compras.create'))->assertOk()->assertSee('Distribuidora Andina');

        $this->actingAs($this->admin())->post(route('compras.store'), [
            'proveedor_id' => $proveedor->id,
            'nro_comprobante' => 'FAC-77',
            'items' => [['producto_id' => $arroz->id, 'cantidad' => 10, 'costo_unitario' => 41.5]],
        ])->assertRedirect(route('compras.index'));

        $compra = Compra::first();
        $this->assertEquals(415.00, (float) $compra->total);
        $this->assertEquals(30, $arroz->fresh()->stock);

        $this->actingAs($this->admin())->get(route('compras.index'))
            ->assertOk()->assertSee('FAC-77')->assertSee('Registrada');
    }

    public function test_validacion_rechaza_compra_sin_proveedor_ni_productos_y_cantidades_invalidas(): void
    {
        $arroz = Producto::where('codigo', 'ABA-001')->first();
        $proveedor = Proveedor::create(['nombre' => 'Uno']);
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('compras.store'), ['items' => []])
            ->assertSessionHasErrors(['proveedor_id', 'items']);

        $this->actingAs($admin)->post(route('compras.store'), [
            'proveedor_id' => $proveedor->id,
            'items' => [['producto_id' => $arroz->id, 'cantidad' => 0, 'costo_unitario' => -5]],
        ])->assertSessionHasErrors(['items.0.cantidad', 'items.0.costo_unitario']);

        $this->assertEquals(0, Compra::count());
        $this->assertEquals(20, $arroz->fresh()->stock);
    }

    public function test_anular_desde_la_pantalla_y_mensaje_cuando_es_imposible(): void
    {
        $arroz = Producto::where('codigo', 'ABA-001')->first();
        $proveedor = Proveedor::create(['nombre' => 'Uno']);
        $admin = $this->admin();
        $item = [['producto_id' => $arroz->id, 'cantidad' => 10, 'costo_unitario' => 40]];

        $this->actingAs($admin)->post(route('compras.store'), ['proveedor_id' => $proveedor->id, 'items' => $item]);
        $this->actingAs($admin)->post(route('compras.store'), ['proveedor_id' => $proveedor->id, 'items' => $item]);
        [$primera, $segunda] = Compra::orderBy('id')->get()->all();

        $this->actingAs($admin)->post(route('compras.anular', $primera))->assertSessionHas('ok');
        $this->assertEquals(Compra::ANULADA, $primera->fresh()->estado);
        $this->assertEquals(30, $arroz->fresh()->stock);

        $arroz->update(['stock' => 2]);   // se vendió después de la segunda compra
        $this->actingAs($admin)->post(route('compras.anular', $segunda))->assertSessionHasErrors('compra');
        $this->assertEquals(Compra::REGISTRADA, $segunda->fresh()->estado);
    }

    public function test_vendedor_recibe_403(): void
    {
        $vendedor = $this->vendedor();
        $compra = Compra::create([
            'usuario_id' => $this->admin()->id,
            'proveedor_id' => Proveedor::create(['nombre' => 'Uno'])->id,
        ]);

        $this->actingAs($vendedor)->get(route('compras.index'))->assertForbidden();
        $this->actingAs($vendedor)->get(route('compras.create'))->assertForbidden();
        $this->actingAs($vendedor)->post(route('compras.store'), [])->assertForbidden();
        $this->actingAs($vendedor)->post(route('compras.anular', $compra))->assertForbidden();
    }
}
