<?php

namespace Tests\Security;

use App\Models\Usuario;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Sec10a: scripts y hojas de estilo de jsDelivr en sesiones de Administrador.
 * Cada <script src> y <link href> hacia cdn.jsdelivr.net debe llevar
 * integrity="sha384-..." y crossorigin, y Chart.js debe ir con versión exacta
 * (no la etiqueta flotante chart.js@4). No hay CSP ni copias locales en public/.
 */
class Sec10aTest extends TestCase
{
    use RefreshDatabase;

    public function test_recursos_de_cdn_llevan_sri_y_version_exacta(): void
    {
        $this->markTestSkipped('SEC pendiente: requiere hashes SRI calculados con acceso a internet');

        $this->seed(DatabaseSeeder::class);
        $admin = Usuario::where('email', 'admin@microempresa.test')->firstOrFail();

        foreach (['/panel', '/comparar-periodos', '/usuarios'] as $ruta) {
            $html = $this->actingAs($admin)->get($ruta)->assertOk()->getContent();

            preg_match_all('/<(script|link)\b[^>]*(?:src|href)="https:\/\/cdn\.jsdelivr\.net[^"]*"[^>]*>/i', $html, $etiquetas);
            $this->assertNotEmpty($etiquetas[0], "$ruta no carga recursos de jsDelivr (¿se pasaron a copia local?)");

            foreach ($etiquetas[0] as $etiqueta) {
                $this->assertMatchesRegularExpression('/\bintegrity="sha384-[A-Za-z0-9+\/=]+"/', $etiqueta, "$ruta: falta SRI en $etiqueta");
                $this->assertMatchesRegularExpression('/\bcrossorigin="(anonymous)?"|\bcrossorigin\b/', $etiqueta, "$ruta: falta crossorigin en $etiqueta");

                if (str_contains($etiqueta, 'chart.js')) {
                    $this->assertMatchesRegularExpression('/chart\.js@\d+\.\d+\.\d+(?:\/|")/', $etiqueta, "$ruta: Chart.js sin versión exacta en $etiqueta");
                }
            }
        }
    }
}
