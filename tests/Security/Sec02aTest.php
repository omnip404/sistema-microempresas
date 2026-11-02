<?php

namespace Tests\Security;

use App\Models\Usuario;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Simula la intercalación de una ráfaga de POST /login concurrentes: entre la lectura del
 * usuario (LoginController:34) y su save() (:96), otras dos peticiones fallidas ya
 * escribieron intentos_fallidos = 2. Con un incremento atómico el tercer fallo debe bloquear.
 */
class Sec02aTest extends TestCase
{
    use RefreshDatabase;

    public function test_fallos_concurrentes_de_login_se_acumulan_y_bloquean_la_cuenta(): void
    {
        $this->seed(DatabaseSeeder::class);

        $email = 'vendedor@microempresa.test';
        $usuario = Usuario::where('email', $email)->first();
        $this->assertSame(0, (int) $usuario->intentos_fallidos);

        $intercalado = false;
        Usuario::retrieved(function (Usuario $u) use (&$intercalado, $email) {
            if (! $intercalado && $u->email === $email) {
                $intercalado = true;
                // Dos peticiones paralelas fallan y guardan mientras esta sigue en curso.
                DB::table($u->getTable())->where('id', $u->id)->update(['intentos_fallidos' => 2]);
            }
        });

        $this->post('/login', ['email' => $email, 'password' => 'incorrecta']);

        $fresco = $usuario->fresh();

        // Comportamiento seguro: 3 fallos en total => cuenta bloqueada.
        $this->assertTrue(
            $fresco->estaBloqueado(),
            'Tras 3 fallos concurrentes la cuenta no quedó bloqueada; intentos_fallidos='.$fresco->intentos_fallidos
        );
    }
}
