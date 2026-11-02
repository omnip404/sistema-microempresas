<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaginasErrorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_invitado_ve_pagina_404_en_espanol(): void
    {
        $this->get('/no-existe')
            ->assertNotFound()
            ->assertSee('Página no encontrada')
            ->assertSee('Volver al inicio');
    }

    public function test_vendedor_ve_pagina_403_en_espanol(): void
    {
        $vendedor = Usuario::where('email', 'vendedor@microempresa.test')->first();

        $this->actingAs($vendedor)->get('/panel')
            ->assertForbidden()
            ->assertSee('Acceso denegado');
    }

    public function test_comparacion_responde_para_administrador(): void
    {
        $admin = Usuario::where('email', 'admin@microempresa.test')->first();

        $this->actingAs($admin)->get('/comparar-periodos')->assertOk();
    }
}
