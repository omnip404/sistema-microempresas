<?php

namespace Tests\Security;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Sec12a: enviar password[] en el login no debe producir un 500 solo para correos de cuentas
 * activas; la respuesta debe ser identica a la de un correo inexistente (error de validacion).
 */
class Sec12aTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_password_como_arreglo_no_revela_si_el_correo_existe(): void
    {
        $existente = $this->from('/login')->post('/login', [
            'email' => 'admin@microempresa.test',
            'password' => ['x'],
        ]);

        $inexistente = $this->from('/login')->post('/login', [
            'email' => 'nadie@microempresa.test',
            'password' => ['x'],
        ]);

        $this->assertNotEquals(500, $existente->getStatusCode(), 'Correo activo con password[] produce 500');
        $this->assertSame($inexistente->getStatusCode(), $existente->getStatusCode());
        $existente->assertSessionHasErrors();
        $this->assertGuest();

        $errExistente = session('errors')?->getBag('default')->toArray();
        $this->assertNotEmpty($errExistente);
    }
}
