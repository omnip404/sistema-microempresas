<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

/**
 * Copia de seguridad de la base de datos (RNF11). SQLite: copia el archivo; MySQL/MariaDB: mysqldump.
 * Se programa a diario en routes/console.php; el servidor debe ejecutar el programador (ver README).
 */
class CrearRespaldo extends Command
{
    protected $signature = 'respaldo:crear {--destino= : Carpeta de destino (por defecto storage/respaldos)}';

    protected $description = 'Crea una copia de seguridad de la base de datos en storage/respaldos con la fecha en el nombre';

    public function handle(): int
    {
        $conexion = config('database.default');
        $config = config("database.connections.{$conexion}");
        $carpeta = $this->option('destino') ?: storage_path('respaldos');
        $sello = now()->format('Ymd-His');

        File::ensureDirectoryExists($carpeta);

        return match ($config['driver']) {
            'sqlite' => $this->respaldarSqlite($config, "{$carpeta}/respaldo-{$sello}.sqlite"),
            'mysql', 'mariadb' => $this->respaldarMysql($config, "{$carpeta}/respaldo-{$sello}.sql"),
            default => $this->fallar("El motor «{$config['driver']}» no tiene respaldo automático todavía."),
        };
    }

    private function respaldarSqlite(array $config, string $destino): int
    {
        $origen = $config['database'];

        if ($origen === ':memory:' || ! is_file($origen)) {
            return $this->fallar("No se encontró el archivo de la base de datos SQLite ({$origen}).");
        }

        if (! copy($origen, $destino)) {
            return $this->fallar("No se pudo copiar la base de datos a {$destino}.");
        }

        $this->info("Respaldo creado: {$destino}");

        return self::SUCCESS;
    }

    private function respaldarMysql(array $config, string $destino): int
    {
        // La contraseña va por variable de entorno para que no aparezca en la lista de procesos.
        $resultado = Process::env(['MYSQL_PWD' => (string) ($config['password'] ?? '')])->run([
            'mysqldump',
            '--host='.$config['host'],
            '--port='.$config['port'],
            '--user='.$config['username'],
            '--single-transaction',
            '--routines',
            '--result-file='.$destino,
            $config['database'],
        ]);

        if (! $resultado->successful()) {
            File::delete($destino);

            return $this->fallar('mysqldump falló: '.trim($resultado->errorOutput() ?: 'sin detalle (¿está mysqldump en el PATH?)'));
        }

        $this->info("Respaldo creado: {$destino}");

        return self::SUCCESS;
    }

    private function fallar(string $mensaje): int
    {
        $this->error($mensaje);

        return self::FAILURE;
    }
}
