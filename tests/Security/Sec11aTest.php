<?php

namespace Tests\Security;

use App\Models\Bitacora;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Sec11a: los intentos de login fallidos y los bloqueos deben quedar en la bitácora
 * con acción propia (LOGIN_FALLIDO / BLOQUEO), la IP y el correo intentado, sin la contraseña.
 * Compatible con la tabla: accion string(30), tabla string(40), registro_id nullable, detalle JSON.
 */
class Sec11aTest extends TestCase
{
    use RefreshDatabase;

    private const IP = '203.0.113.9';

    private const CLAVE = 'ClaveMalaSecreta123';

    private function fallar(string $email): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => self::IP])
            ->post('/login', ['email' => $email, 'password' => self::CLAVE])
            ->assertSessionHasErrors('email');
    }

    private function filasCon(string $accion, string $email)
    {
        return Bitacora::where('accion', $accion)->get()->filter(
            fn ($f) => $f->ip === self::IP && str_contains(json_encode($f->detalle), $email)
        );
    }

    public function test_fallos_y_bloqueo_quedan_en_bitacora_con_ip_y_correo(): void
    {
        $this->seed(DatabaseSeeder::class);

        $inexistente = 'nadie@ejemplo.test';
        $this->fallar($inexistente);
        $this->assertCount(1, $this->filasCon('LOGIN_FALLIDO', $inexistente), 'Fallo con correo inexistente sin rastro');

        $admin = 'admin@microempresa.test';
        $this->fallar($admin);
        $this->assertCount(1, $this->filasCon('LOGIN_FALLIDO', $admin), 'Fallo con correo existente sin rastro');

        $this->fallar($admin);
        $this->fallar($admin);
        $this->assertGreaterThanOrEqual(1, Bitacora::where('accion', 'BLOQUEO')->where('ip', self::IP)->count(), 'Bloqueo sin fila BLOQUEO');

        foreach (Bitacora::all() as $fila) {
            $this->assertStringNotContainsString(self::CLAVE, json_encode($fila->toArray()), 'La contraseña quedó en la bitácora');
        }
    }
}
