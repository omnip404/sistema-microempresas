<?php

namespace Tests\Security;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Sec02b: el mensaje de error del login no debe permitir distinguir un correo
 * existente de uno inexistente (enumeración vía mensaje de bloqueo).
 */
class Sec02bTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    private function mensajeTrasCuatroIntentos(string $email): string
    {
        $respuesta = null;
        foreach (range(1, 4) as $intento) {
            $this->flushSession();
            $respuesta = $this->post('/login', ['email' => $email, 'password' => 'incorrecta']);
        }

        return session('errors')->first('email');
    }

    public function test_correo_existente_e_inexistente_producen_el_mismo_mensaje(): void
    {
        $existente = $this->mensajeTrasCuatroIntentos('admin@microempresa.test');
        $inexistente = $this->mensajeTrasCuatroIntentos('no-existe@microempresa.test');

        $this->assertSame($inexistente, $existente,
            'El mensaje de error revela qué correos existen en el sistema.');
    }
}
