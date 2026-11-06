<?php

namespace Tests\Feature;

use App\Models\Producto;
use App\Models\Usuario;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Detalles encontrados en la depuración del 05/11/2026. */
class CorreccionesDepuracionTest extends TestCase
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

    public function test_el_mensaje_de_venta_usa_formato_boliviano(): void
    {
        $arroz = Producto::where('codigo', 'ABA-001')->first();
        $arroz->update(['precio_venta' => 1234.5, 'stock' => 10]);

        $this->actingAs($this->usuario('vendedor@microempresa.test'))
            ->post('/ventas', ['items' => [['producto_id' => $arroz->id, 'cantidad' => 1]]])
            ->assertSessionHas('ok', fn ($m) => str_contains($m, 'Bs 1.234,50'));
    }

    public function test_el_vendedor_no_vuelve_a_una_pagina_de_administrador_tras_el_login(): void
    {
        $this->get('/panel')->assertRedirect('/login');

        $this->post('/login', ['email' => 'vendedor@microempresa.test', 'password' => 'password'])
            ->assertRedirect(route('ventas.create'));
    }

    public function test_el_vendedor_si_vuelve_a_una_pagina_permitida_tras_el_login(): void
    {
        $this->get('/clientes')->assertRedirect('/login');

        $this->post('/login', ['email' => 'vendedor@microempresa.test', 'password' => 'password'])
            ->assertRedirect(route('clientes.index'));
    }

    public function test_el_administrador_vuelve_a_la_pagina_que_pidio(): void
    {
        $this->get('/reportes')->assertRedirect('/login');

        $this->post('/login', ['email' => 'admin@microempresa.test', 'password' => 'password'])
            ->assertRedirect(route('reportes.index'));
    }

    public function test_el_buscador_trata_porcentaje_y_guion_bajo_como_texto(): void
    {
        $base = Producto::first()->only(['categoria_id', 'unidad_medida', 'precio_compra', 'precio_venta']);
        Producto::create($base + ['codigo' => 'DES-50', 'nombre' => 'Detergente 50% extra', 'stock' => 5, 'stock_minimo' => 1]);
        Producto::create($base + ['codigo' => 'DES-51', 'nombre' => 'Detergente 500 g', 'stock' => 5, 'stock_minimo' => 1]);

        $admin = $this->usuario('admin@microempresa.test');

        $this->actingAs($admin)->get('/productos?buscar=50%25')
            ->assertSee('Detergente 50% extra')
            ->assertDontSee('Detergente 500 g');

        $this->actingAs($admin)->get('/productos?buscar=_')
            ->assertDontSee('Detergente 500 g');
    }

    public function test_el_periodo_no_puede_pasar_de_dos_anios(): void
    {
        $admin = $this->usuario('admin@microempresa.test');

        $this->actingAs($admin)->get('/panel?desde=2020-01-01&hasta=2026-01-01')
            ->assertSessionHasErrors('hasta');

        $this->actingAs($admin)->get('/panel?desde=2025-01-01&hasta=2026-12-31')
            ->assertOk();
    }
}
