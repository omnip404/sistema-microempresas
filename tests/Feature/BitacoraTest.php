<?php

namespace Tests\Feature;

use App\Models\Bitacora;
use App\Models\Usuario;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BitacoraTest extends TestCase
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

    public function test_login_y_logout_quedan_registrados(): void
    {
        $vendedor = $this->vendedor();

        $this->post('/login', ['email' => $vendedor->email, 'password' => 'password']);
        $this->assertDatabaseHas('bitacora', ['usuario_id' => $vendedor->id, 'accion' => 'INGRESO', 'tabla' => 'usuarios', 'registro_id' => $vendedor->id]);

        $this->post('/logout');
        $this->assertDatabaseHas('bitacora', ['usuario_id' => $vendedor->id, 'accion' => 'SALIDA', 'tabla' => 'usuarios', 'registro_id' => $vendedor->id]);
    }

    public function test_un_ingreso_fallido_no_registra_ingreso(): void
    {
        $this->post('/login', ['email' => $this->vendedor()->email, 'password' => 'incorrecta']);

        $this->assertDatabaseMissing('bitacora', ['accion' => 'INGRESO']);
    }

    public function test_administrador_ve_la_bitacora_con_detalle_legible(): void
    {
        Bitacora::create([
            'usuario_id' => $this->admin()->id, 'accion' => 'CREAR', 'tabla' => 'productos', 'registro_id' => 99,
            'detalle' => ['nombre' => 'Azúcar morena', 'precio_venta' => '12.50'], 'ip' => '127.0.0.1',
        ]);

        $this->actingAs($this->admin())->get(route('bitacora.index'))
            ->assertOk()
            ->assertSee('Azúcar morena')
            ->assertSee('precio_venta:')
            ->assertDontSee('{"nombre"', false);
    }

    public function test_filtros_por_usuario_tabla_y_fechas(): void
    {
        $admin = $this->admin();
        $vendedor = $this->vendedor();
        Bitacora::query()->delete();

        $antiguo = Bitacora::create(['usuario_id' => $vendedor->id, 'accion' => 'CREAR', 'tabla' => 'ventas', 'registro_id' => 1]);
        $antiguo->forceFill(['created_at' => '2026-01-10 10:00:00'])->save();
        Bitacora::create(['usuario_id' => $admin->id, 'accion' => 'CREAR', 'tabla' => 'productos', 'registro_id' => 2]);

        $this->actingAs($admin);

        $this->get(route('bitacora.index', ['tabla' => 'ventas']))->assertSee('ventas')->assertDontSee('productos</td>', false);
        $this->get(route('bitacora.index', ['usuario_id' => $admin->id]))->assertSee('productos</td>', false)->assertDontSee('ventas</td>', false);
        $this->get(route('bitacora.index', ['desde' => '2026-01-01', 'hasta' => '2026-01-31']))
            ->assertSee('ventas</td>', false)->assertDontSee('productos</td>', false);
        $this->get(route('bitacora.index', ['desde' => '2026-02-01']))
            ->assertSee('productos</td>', false)->assertDontSee('ventas</td>', false);
    }

    public function test_rango_de_fechas_invalido_se_rechaza(): void
    {
        $this->actingAs($this->admin())
            ->get(route('bitacora.index', ['desde' => '2026-03-10', 'hasta' => '2026-03-01']))
            ->assertSessionHasErrors('hasta');
    }

    public function test_pagina_de_25_filas(): void
    {
        Bitacora::query()->delete();
        for ($i = 1; $i <= 30; $i++) {
            Bitacora::create(['accion' => 'CREAR', 'tabla' => 'productos', 'registro_id' => $i]);
        }

        $respuesta = $this->actingAs($this->admin())->get(route('bitacora.index'));
        $this->assertCount(25, $respuesta->viewData('registros')->items());
        $this->assertEquals(30, $respuesta->viewData('registros')->total());
    }

    public function test_vendedor_recibe_403(): void
    {
        $this->actingAs($this->vendedor())->get(route('bitacora.index'))->assertForbidden();
    }
}
