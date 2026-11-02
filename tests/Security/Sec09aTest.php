<?php

namespace Tests\Security;

use App\Models\Usuario;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Sec09a: credenciales semilla conocidas. El README manda ejecutar
 * `php artisan migrate --seed` y publica admin@microempresa.test / "password".
 * En entorno production el seeder no debe dejar cuentas con esa contraseña
 * conocida (debe omitirlas o tomar la contraseña de una variable de entorno),
 * y un anónimo no debe poder entrar como Administrador con ella.
 * En testing/local el seeder sigue igual, así que el resto de la suite no cambia.
 */
class Sec09aTest extends TestCase
{
    use RefreshDatabase;

    public function test_en_produccion_el_seeder_no_deja_cuentas_con_password_conocida(): void
    {
        $this->app->detectEnvironment(fn () => 'production');
        $this->assertTrue($this->app->environment('production'));

        // Igual que `php artisan migrate --seed --force` en un despliegue.
        $this->artisan('db:seed', ['--class' => DatabaseSeeder::class, '--force' => true])->assertSuccessful();

        foreach (Usuario::all() as $usuario) {
            $this->assertFalse(
                Hash::check('password', $usuario->password),
                "La cuenta {$usuario->email} quedó con la contraseña publicada en el README."
            );
        }

        $this->post('/login', ['email' => 'admin@microempresa.test', 'password' => 'password']);
        $this->assertGuest();
    }

    public function test_en_testing_el_seeder_sigue_creando_las_cuentas_de_prueba(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = Usuario::where('email', 'admin@microempresa.test')->first();
        $this->assertNotNull($admin);
        $this->assertTrue(Hash::check('password', $admin->password));
    }
}
