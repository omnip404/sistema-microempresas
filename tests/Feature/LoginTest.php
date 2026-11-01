<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_administrador_ingresa_y_va_al_panel(): void
    {
        $this->post('/login', ['email' => 'admin@microempresa.test', 'password' => 'password'])
            ->assertRedirect(route('panel'));
    }

    public function test_vendedor_ingresa_y_va_a_registrar_venta(): void
    {
        $this->post('/login', ['email' => 'vendedor@microempresa.test', 'password' => 'password'])
            ->assertRedirect(route('ventas.create'));
    }

    public function test_tres_intentos_fallidos_bloquean_la_cuenta_5_minutos(): void
    {
        foreach (range(1, 3) as $intento) {
            $this->post('/login', ['email' => 'vendedor@microempresa.test', 'password' => 'incorrecta']);
        }

        $usuario = Usuario::where('email', 'vendedor@microempresa.test')->first();
        $this->assertTrue($usuario->estaBloqueado());

        // Aun con la contraseña correcta, no puede ingresar mientras dure el bloqueo
        $this->post('/login', ['email' => 'vendedor@microempresa.test', 'password' => 'password'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->travel(6)->minutes();
        $this->post('/login', ['email' => 'vendedor@microempresa.test', 'password' => 'password'])
            ->assertRedirect(route('ventas.create'));
    }
}
