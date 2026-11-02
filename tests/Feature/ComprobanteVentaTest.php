<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Producto;
use App\Models\Rol;
use App\Models\Usuario;
use App\Models\Venta;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComprobanteVentaTest extends TestCase
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

    private function ventaDelVendedor(): Venta
    {
        $cliente = Cliente::create(['nombre' => 'Marta Quispe']);
        $arroz = Producto::where('codigo', 'ABA-001')->first();   // Bs 55

        $this->actingAs($this->vendedor())->post('/ventas', [
            'cliente_id' => $cliente->id,
            'items' => [['producto_id' => $arroz->id, 'cantidad' => 2]],
        ]);

        return Venta::firstOrFail();
    }

    public function test_el_vendedor_dueno_de_la_venta_ve_el_comprobante_con_su_contenido(): void
    {
        $venta = $this->ventaDelVendedor();

        $this->actingAs($this->vendedor())
            ->get(route('ventas.comprobante', $venta))
            ->assertOk()
            ->assertSee(config('app.name'))
            ->assertSee("Comprobante de venta N° {$venta->id}")
            ->assertSee('Marta Quispe')
            ->assertSee('Vendedor')
            ->assertSee('Registrada')
            ->assertSee('110,00');   // 2 × 55
    }

    public function test_el_administrador_ve_cualquier_comprobante(): void
    {
        $venta = $this->ventaDelVendedor();

        $this->actingAs($this->admin())
            ->get(route('ventas.comprobante', $venta))
            ->assertOk();
    }

    public function test_otro_vendedor_recibe_403(): void
    {
        $venta = $this->ventaDelVendedor();
        $otro = Usuario::create([
            'rol_id' => Rol::where('nombre', Rol::VENDEDOR)->value('id'),
            'nombre' => 'Otro vendedor',
            'email' => 'otro@microempresa.test',
            'password' => 'password',
        ]);

        $this->actingAs($otro)
            ->get(route('ventas.comprobante', $venta))
            ->assertForbidden();
    }

    public function test_un_invitado_es_enviado_al_login(): void
    {
        $venta = $this->ventaDelVendedor();
        auth()->logout();

        $this->get(route('ventas.comprobante', $venta))->assertRedirect(route('login'));
    }

    public function test_tras_registrar_la_venta_se_ofrece_imprimir_el_comprobante(): void
    {
        $arroz = Producto::where('codigo', 'ABA-001')->first();

        $respuesta = $this->actingAs($this->vendedor())
            ->post('/ventas', ['items' => [['producto_id' => $arroz->id, 'cantidad' => 1]]]);

        $venta = Venta::firstOrFail();
        $respuesta->assertRedirect(route('ventas.create'))
            ->assertSessionHas('comprobante', route('ventas.comprobante', $venta));

        $this->followRedirects($respuesta)->assertSee('Imprimir comprobante');
    }

    public function test_el_listado_de_ventas_enlaza_al_comprobante(): void
    {
        $venta = $this->ventaDelVendedor();

        $this->actingAs($this->admin())
            ->get(route('ventas.index'))
            ->assertOk()
            ->assertSee(route('ventas.comprobante', $venta));
    }
}
