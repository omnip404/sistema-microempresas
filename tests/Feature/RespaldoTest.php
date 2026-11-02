<?php

namespace Tests\Feature;

use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Tests\TestCase;

class RespaldoTest extends TestCase
{
    private string $carpeta;

    protected function setUp(): void
    {
        parent::setUp();
        $this->carpeta = sys_get_temp_dir().'/respaldos-prueba-'.uniqid();
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->carpeta);
        parent::tearDown();
    }

    public function test_sqlite_copia_el_archivo_con_la_fecha_en_el_nombre(): void
    {
        File::ensureDirectoryExists($this->carpeta);
        $origen = $this->carpeta.'/origen.sqlite';
        file_put_contents($origen, 'contenido de la base');
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => $origen]);

        $destino = $this->carpeta.'/copias';
        $this->artisan('respaldo:crear', ['--destino' => $destino])->assertSuccessful();

        $copias = File::files($destino);
        $this->assertCount(1, $copias);
        $this->assertMatchesRegularExpression('/^respaldo-\d{8}-\d{6}\.sqlite$/', $copias[0]->getFilename());
        $this->assertSame('contenido de la base', file_get_contents($copias[0]->getPathname()));
    }

    public function test_sqlite_en_memoria_o_inexistente_falla_con_mensaje(): void
    {
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);

        $this->artisan('respaldo:crear', ['--destino' => $this->carpeta])
            ->expectsOutputToContain('No se encontró el archivo de la base de datos SQLite')
            ->assertFailed();

        $this->assertSame([], File::files($this->carpeta));
    }

    public function test_mysql_ejecuta_mysqldump_con_la_clave_en_el_entorno(): void
    {
        Process::fake();
        config([
            'database.default' => 'mysql',
            'database.connections.mysql.host' => 'db.local',
            'database.connections.mysql.port' => '3307',
            'database.connections.mysql.username' => 'respaldo',
            'database.connections.mysql.password' => 'secreta',
            'database.connections.mysql.database' => 'microempresa_db',
        ]);

        $this->artisan('respaldo:crear', ['--destino' => $this->carpeta])
            ->expectsOutputToContain('Respaldo creado')
            ->assertSuccessful();

        Process::assertRan(function ($proceso) {
            $orden = implode(' ', (array) $proceso->command);

            return str_starts_with($orden, 'mysqldump ')
                && str_contains($orden, '--host=db.local')
                && str_contains($orden, '--port=3307')
                && str_contains($orden, '--user=respaldo')
                && str_contains($orden, '--single-transaction')
                && preg_match('#--result-file=.*respaldo-\d{8}-\d{6}\.sql microempresa_db$#', $orden) === 1
                && ! str_contains($orden, 'secreta')                  // la clave no va en la línea de comandos
                && ($proceso->environment['MYSQL_PWD'] ?? null) === 'secreta';
        });
    }

    public function test_mysql_con_error_de_mysqldump_falla_y_lo_informa(): void
    {
        Process::fake(['*' => Process::result(errorOutput: 'Access denied', exitCode: 2)]);
        config(['database.default' => 'mysql']);

        $this->artisan('respaldo:crear', ['--destino' => $this->carpeta])
            ->expectsOutputToContain('mysqldump falló: Access denied')
            ->assertFailed();
    }

    public function test_motor_sin_soporte_falla_con_mensaje(): void
    {
        config(['database.default' => 'pgsql']);

        $this->artisan('respaldo:crear', ['--destino' => $this->carpeta])
            ->expectsOutputToContain('no tiene respaldo automático')
            ->assertFailed();
    }

    public function test_el_respaldo_esta_programado_a_diario(): void
    {
        $evento = collect(app(Schedule::class)->events())
            ->first(fn (Event $e) => str_contains($e->command, 'respaldo:crear'));

        $this->assertNotNull($evento, 'respaldo:crear no está en el programador.');
        $this->assertSame('0 0 * * *', $evento->expression);
    }
}
