<?php

namespace Tests\Security;

use App\Models\Usuario;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Sec12b (SEC-17): `buscar[]=x` no debe provocar un 500 en los listados.
 * Comportamiento seguro: 200 (lista) o 302/422 (error de validación).
 */
class Sec12bTest extends TestCase
{
    use RefreshDatabase;

    private function assertNoEs500($respuesta): void
    {
        $this->assertContains($respuesta->status(), [200, 302, 422], 'Respondió '.$respuesta->status());
    }

    public function test_vendedor_buscar_arreglo_en_clientes_no_da_500(): void
    {
        $this->seed(DatabaseSeeder::class);
        $vendedor = Usuario::where('email', 'vendedor@microempresa.test')->firstOrFail();

        $this->assertNoEs500($this->actingAs($vendedor)->get('/clientes?buscar[]=x'));
    }

    public function test_admin_buscar_arreglo_en_productos_y_proveedores_no_da_500(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = Usuario::where('email', 'admin@microempresa.test')->firstOrFail();

        $this->assertNoEs500($this->actingAs($admin)->get('/productos?buscar[]=x'));
        $this->assertNoEs500($this->actingAs($admin)->get('/proveedores?buscar[]=x'));
    }
}
