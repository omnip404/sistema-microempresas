<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocalizacionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_validacion_del_login_devuelve_mensaje_en_espanol(): void
    {
        $respuesta = $this->from('/login')->post('/login', ['password' => 'cualquiera']);

        $respuesta->assertSessionHasErrors('email');
        $mensaje = session('errors')->first('email');

        $this->assertStringContainsString('obligatorio', $mensaje);
        $this->assertStringContainsString('correo electrónico', $mensaje);
        $this->assertStringNotContainsString('required', $mensaje);
    }

    public function test_configuracion_de_zona_horaria_e_idioma(): void
    {
        $this->assertSame('America/La_Paz', config('app.timezone'));
        $this->assertSame('es', config('app.locale'));
        $this->assertSame('es', config('app.fallback_locale'));
    }
}
