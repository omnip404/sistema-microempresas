<?php

namespace Tests\Feature;

use App\Models\Rol;
use App\Models\Usuario;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UsuarioTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    private function admin(): Usuario
    {
        return Usuario::where('email', 'admin@microempresa.test')->first();
    }

    private function vendedor(): Usuario
    {
        return Usuario::where('email', 'vendedor@microempresa.test')->first();
    }

    private function rolId(string $nombre): int
    {
        return Rol::where('nombre', $nombre)->value('id');
    }

    public function test_administrador_crea_usuario_con_contraseña_cifrada(): void
    {
        $this->actingAs($this->admin())
            ->post(route('usuarios.store'), [
                'nombre' => 'Rosa Choque', 'email' => 'rosa@microempresa.test',
                'rol_id' => $this->rolId(Rol::VENDEDOR), 'password' => 'clave-segura', 'activo' => 1,
            ])
            ->assertRedirect(route('usuarios.index'));

        $nuevo = Usuario::where('email', 'rosa@microempresa.test')->first();
        $this->assertNotNull($nuevo);
        $this->assertTrue(Hash::check('clave-segura', $nuevo->password));
        $this->assertDatabaseHas('bitacora', ['tabla' => 'usuarios', 'accion' => 'CREAR', 'registro_id' => $nuevo->id]);
    }

    public function test_validaciones_de_correo_unico_y_contraseña_minima(): void
    {
        $admin = $this->admin();
        $base = ['nombre' => 'X', 'rol_id' => $this->rolId(Rol::VENDEDOR)];

        $this->actingAs($admin)->post(route('usuarios.store'), $base + ['email' => 'admin@microempresa.test', 'password' => 'clave-segura'])
            ->assertSessionHasErrors('email');
        $this->actingAs($admin)->post(route('usuarios.store'), $base + ['email' => 'otro@microempresa.test', 'password' => '1234567'])
            ->assertSessionHasErrors('password');
        $this->actingAs($admin)->post(route('usuarios.store'), $base + ['email' => 'otro@microempresa.test'])
            ->assertSessionHasErrors('password');
    }

    public function test_editar_conserva_la_contraseña_si_no_se_escribe_otra(): void
    {
        $vendedor = $this->vendedor();
        $hashAntes = $vendedor->password;

        $this->actingAs($this->admin())
            ->put(route('usuarios.update', $vendedor), [
                'nombre' => 'Vendedor Nuevo', 'email' => $vendedor->email, 'rol_id' => $vendedor->rol_id, 'password' => '', 'activo' => 1,
            ])
            ->assertSessionHasNoErrors();

        $vendedor->refresh();
        $this->assertEquals('Vendedor Nuevo', $vendedor->nombre);
        $this->assertEquals($hashAntes, $vendedor->password);

        $this->actingAs($this->admin())
            ->put(route('usuarios.update', $vendedor), [
                'nombre' => 'Vendedor Nuevo', 'email' => $vendedor->email, 'rol_id' => $vendedor->rol_id, 'password' => 'otra-clave-1', 'activo' => 1,
            ]);
        $this->assertTrue(Hash::check('otra-clave-1', $vendedor->fresh()->password));
    }

    public function test_activar_y_desactivar_y_usuario_desactivado_no_inicia_sesion(): void
    {
        $vendedor = $this->vendedor();

        $this->actingAs($this->admin())->post(route('usuarios.estado', $vendedor))->assertSessionHas('ok');
        $this->assertFalse($vendedor->fresh()->activo);

        auth()->logout();
        $this->post('/login', ['email' => $vendedor->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->actingAs($this->admin())->post(route('usuarios.estado', $vendedor));
        $this->assertTrue($vendedor->fresh()->activo);
    }

    public function test_administrador_no_puede_desactivarse_a_si_mismo(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('usuarios.estado', $admin))->assertSessionHasErrors('usuario');
        $this->actingAs($admin)
            ->put(route('usuarios.update', $admin), [
                'nombre' => $admin->nombre, 'email' => $admin->email, 'rol_id' => $admin->rol_id, 'activo' => 0,
            ])
            ->assertSessionHasErrors('activo');

        $this->assertTrue($admin->fresh()->activo);
    }

    public function test_desbloquear_pone_en_cero_los_intentos_y_el_bloqueo(): void
    {
        $vendedor = $this->vendedor();
        $vendedor->forceFill(['intentos_fallidos' => 2, 'bloqueado_hasta' => now()->addMinutes(5)])->save();
        $this->assertTrue($vendedor->fresh()->estaBloqueado());

        $this->actingAs($this->admin())->post(route('usuarios.desbloquear', $vendedor))->assertSessionHas('ok');

        $vendedor->refresh();
        $this->assertEquals(0, $vendedor->intentos_fallidos);
        $this->assertNull($vendedor->bloqueado_hasta);
        $this->assertFalse($vendedor->estaBloqueado());
    }

    public function test_vendedor_recibe_403(): void
    {
        $vendedor = $this->vendedor();

        $this->actingAs($vendedor)->get(route('usuarios.index'))->assertForbidden();
        $this->actingAs($vendedor)->get(route('usuarios.create'))->assertForbidden();
        $this->actingAs($vendedor)->post(route('usuarios.store'), [])->assertForbidden();
        $this->actingAs($vendedor)->post(route('usuarios.estado', $this->admin()))->assertForbidden();
        $this->actingAs($vendedor)->post(route('usuarios.desbloquear', $this->admin()))->assertForbidden();
    }
}
