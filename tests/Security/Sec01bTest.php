<?php

namespace Tests\Security;

use App\Models\Cliente;
use App\Models\Usuario;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Sec01b: una cuenta desactivada por el Administrador no debe seguir operando
 * con la sesion que ya tenia abierta.
 */
class Sec01bTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_vendedor_desactivado_con_sesion_abierta_no_puede_seguir_operando(): void
    {
        $admin = Usuario::where('email', 'admin@microempresa.test')->first();
        $vendedor = Usuario::where('email', 'vendedor@microempresa.test')->first();

        // El administrador desactiva la cuenta del vendedor.
        $this->actingAs($admin)
            ->post(route('usuarios.estado', $vendedor))
            ->assertRedirect();
        $this->assertFalse($vendedor->fresh()->activo);

        // El vendedor conserva su sesion (cookie) previa y sigue enviando peticiones.
        $respuesta = $this->actingAs($vendedor->fresh())
            ->post(route('clientes.store'), ['nombre' => 'Cliente fantasma', 'ci_nit' => '123', 'telefono' => '70000000']);

        // Comportamiento seguro: la peticion se rechaza y no se crea nada.
        $this->assertTrue(
            $respuesta->isRedirect(route('login')) || $respuesta->status() === 403,
            'Una cuenta desactivada no debe poder operar; status '.$respuesta->status()
        );
        $this->assertNull(Cliente::where('nombre', 'Cliente fantasma')->first());
    }
}
