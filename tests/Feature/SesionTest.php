<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * RNF05: la sesión se cierra tras 15 minutos de inactividad. Se revisan los archivos de
 * configuración (no el .env local, que cada equipo puede tener con valores anteriores).
 */
class SesionTest extends TestCase
{
    public function test_env_example_fija_la_sesion_en_15_minutos(): void
    {
        $this->assertMatchesRegularExpression(
            '/^SESSION_LIFETIME=15\s*$/m',
            file_get_contents(base_path('.env.example'))
        );
    }

    public function test_el_valor_por_defecto_de_la_configuracion_es_15_minutos(): void
    {
        $this->assertMatchesRegularExpression(
            "/'lifetime'\s*=>\s*\(int\)\s*env\('SESSION_LIFETIME',\s*15\)/",
            file_get_contents(config_path('session.php'))
        );
    }

    public function test_sin_variable_de_entorno_la_sesion_dura_15_minutos(): void
    {
        // Con el valor por defecto de la configuración (sin variable de entorno) la duración es 15.
        putenv('SESSION_LIFETIME');
        unset($_ENV['SESSION_LIFETIME'], $_SERVER['SESSION_LIFETIME']);

        $config = require config_path('session.php');

        $this->assertSame(15, $config['lifetime']);
    }

    public function test_el_readme_documenta_la_sesion_de_15_minutos(): void
    {
        $this->assertStringContainsString('15 minutos de inactividad', file_get_contents(base_path('README.md')));
    }
}
