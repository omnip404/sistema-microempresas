<?php

namespace Tests\Security;

use App\Models\Cliente;
use App\Models\Usuario;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Sec02d: al restablecer la contrasena de una cuenta, las sesiones abiertas
 * antes del cambio (por ejemplo, una cookie robada) deben dejar de valer.
 */
class Sec02dTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_sesion_previa_deja_de_valer_tras_cambiar_la_contrasena(): void
    {
        $admin = Usuario::where('email', 'admin@microempresa.test')->first();
        $vendedor = Usuario::where('email', 'vendedor@microempresa.test')->first();
        $vendedor->forceFill(['password' => 'clave-original-1'])->save();

        // 1. El vendedor inicia sesion de verdad (POST /login); el atacante copia esa sesion.
        $this->post('/login', ['email' => $vendedor->email, 'password' => 'clave-original-1'])
            ->assertRedirect();
        $this->assertAuthenticatedAs($vendedor);
        $sesionRobada = session()->all();

        // 2. El administrador restablece la contrasena del vendedor desde otra sesion.
        $this->flushSession();
        app('auth')->forgetGuards();
        $this->actingAs($admin)
            ->put(route('usuarios.update', $vendedor), [
                'nombre' => $vendedor->nombre,
                'email' => $vendedor->email,
                'rol_id' => $vendedor->rol_id,
                'password' => 'clave-nueva-segura-2',
                'activo' => 1,
            ])
            ->assertRedirect(route('usuarios.index'));
        $this->assertNotSame($sesionRobada['password_hash_web'] ?? null, $vendedor->fresh()->password);

        // 3. El atacante reenvia la cookie robada (mismo contenido de sesion).
        $this->flushSession();
        app('auth')->forgetGuards();
        $respuesta = $this->withSession($sesionRobada)
            ->post(route('clientes.store'), ['nombre' => 'Cliente del atacante', 'ci_nit' => '999', 'telefono' => '70000001']);

        // Comportamiento seguro: la sesion previa al cambio se rechaza y no se crea nada.
        $this->assertTrue(
            $respuesta->isRedirect(route('login')) || $respuesta->status() === 401,
            'La sesion anterior al cambio de contrasena sigue activa; status '.$respuesta->status()
        );
        $this->assertNull(Cliente::where('nombre', 'Cliente del atacante')->first());
    }
}
