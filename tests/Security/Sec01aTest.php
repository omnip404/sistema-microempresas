<?php

namespace Tests\Security;

use App\Models\Rol;
use App\Models\Usuario;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Sec01aTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_ultimo_administrador_no_puede_quitarse_su_propio_rol(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = Usuario::where('email', 'admin@microempresa.test')->first();
        $rolAdmin = Rol::where('nombre', Rol::ADMINISTRADOR)->value('id');
        $rolVendedor = Rol::where('nombre', Rol::VENDEDOR)->value('id');

        $this->actingAs($admin)->put(route('usuarios.update', $admin), [
            'nombre' => $admin->nombre,
            'email' => $admin->email,
            'rol_id' => $rolVendedor,
            'activo' => 1,
        ]);

        // Comportamiento seguro: debe seguir existiendo al menos un administrador activo
        $this->assertSame($rolAdmin, (int) $admin->fresh()->rol_id);
        $this->assertGreaterThan(0, Usuario::where('rol_id', $rolAdmin)->where('activo', true)->count());
    }
}
