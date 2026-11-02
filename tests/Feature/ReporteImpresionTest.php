<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ReporteImpresionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        Carbon::setTestNow('2026-10-20 15:30:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function admin(): Usuario
    {
        return Usuario::where('email', 'admin@microempresa.test')->first();
    }

    public function test_el_reporte_incluye_encabezado_solo_para_impresion(): void
    {
        $this->actingAs($this->admin())
            ->get(route('reportes.index', ['tipo' => 'ventas', 'desde' => '2026-10-01', 'hasta' => '2026-10-20']))
            ->assertOk()
            ->assertSee('d-none d-print-block', false)
            ->assertSeeInOrder([
                'encabezado-impresion',
                config('app.name'),
                'Ventas por día',
                'Periodo: del 01/10/2026 al 20/10/2026',
                'Emitido el 20/10/2026 15:30',
            ]);
    }

    public function test_los_reportes_sin_periodo_indican_la_situacion_a_la_fecha(): void
    {
        $this->actingAs($this->admin())
            ->get(route('reportes.index', ['tipo' => 'inventario']))
            ->assertOk()
            ->assertSee('Situación al 20/10/2026')
            ->assertSee('Emitido el 20/10/2026 15:30');
    }

    public function test_la_hoja_de_estilos_numera_las_paginas_al_imprimir(): void
    {
        $css = file_get_contents(public_path('css/app.css'));

        $this->assertStringContainsString('@page', $css);
        $this->assertStringContainsString('counter(page)', $css);
    }

    public function test_vendedor_no_ve_el_reporte(): void
    {
        $vendedor = Usuario::where('email', 'vendedor@microempresa.test')->first();

        $this->actingAs($vendedor)->get(route('reportes.index'))->assertForbidden();
    }
}
