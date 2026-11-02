<?php

namespace Tests\Feature;

use App\Models\Usuario;
use App\Models\Venta;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\RendimientoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

/**
 * RNF01: el panel y las consultas responden en menos de 3 segundos con 10 000 ventas.
 * Corre con toda la suite (≈ 5 s); para ejecutarla sola: php artisan test --group=rendimiento
 * Los tiempos medidos se imprimen en la salida de errores para anotarlos en LOOP-LOG-2.md.
 */
#[Group('rendimiento')]
class RendimientoTest extends TestCase
{
    use RefreshDatabase;

    private const LIMITE_SEGUNDOS = 3.0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->seed(RendimientoSeeder::class);
    }

    private function admin(): Usuario
    {
        return Usuario::where('email', 'admin@microempresa.test')->first();
    }

    /** Pide la URL como administrador y comprueba que responda bien y dentro del límite. */
    private function medir(string $nombre, string $url): float
    {
        $inicio = hrtime(true);
        $respuesta = $this->actingAs($this->admin())->get($url);
        $respuesta->assertOk();
        if ($respuesta->baseResponse instanceof StreamedResponse) {
            $respuesta->streamedContent();
        }
        $segundos = (hrtime(true) - $inicio) / 1e9;

        fwrite(STDERR, sprintf("\n[rendimiento] %-40s %.3f s", $nombre, $segundos));
        $this->assertLessThan(self::LIMITE_SEGUNDOS, $segundos, "«{$nombre}» tardó {$segundos} s (límite ".self::LIMITE_SEGUNDOS.' s).');

        return $segundos;
    }

    public function test_el_seeder_crea_10000_ventas(): void
    {
        $this->assertSame(RendimientoSeeder::VENTAS, Venta::count());
        $this->assertGreaterThan(0, Venta::where('estado', Venta::ANULADA)->count());
    }

    public function test_panel_responde_en_menos_de_3_segundos(): void
    {
        $this->medir('Panel (mes actual)', route('panel'));
        $this->medir('Panel (12 meses)', route('panel', ['desde' => now()->subYear()->toDateString(), 'hasta' => now()->toDateString()]));
    }

    public function test_reportes_responden_en_menos_de_3_segundos(): void
    {
        $periodo = ['desde' => now()->subYear()->toDateString(), 'hasta' => now()->toDateString()];

        foreach (['ventas', 'mas-vendidos', 'compras', 'inventario', 'stock-bajo'] as $tipo) {
            $this->medir("Reporte {$tipo} (12 meses)", route('reportes.index', ['tipo' => $tipo] + $periodo));
        }

        $this->medir('Exportar CSV de ventas (12 meses)', route('reportes.exportar', ['tipo' => 'ventas'] + $periodo));
    }

    public function test_comparacion_y_listado_de_ventas_responden_en_menos_de_3_segundos(): void
    {
        $this->medir('Comparar periodos', route('comparacion.index'));
        $this->medir('Listado de ventas', route('ventas.index'));
    }
}
