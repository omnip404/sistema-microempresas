<?php

namespace Tests\Feature;

use App\Models\Proveedor;
use App\Models\Usuario;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProveedorTest extends TestCase
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

    public function test_administrador_crea_proveedor_y_queda_en_bitacora(): void
    {
        $this->actingAs($this->admin())
            ->post(route('proveedores.store'), [
                'nombre' => 'Distribuidora Andina', 'nit' => '1020304050', 'telefono' => '70011223',
                'direccion' => 'Av. Satélite 123', 'activo' => 1,
            ])
            ->assertRedirect(route('proveedores.index'));

        $this->assertDatabaseHas('proveedores', ['nombre' => 'Distribuidora Andina', 'nit' => '1020304050']);
        $this->assertDatabaseHas('bitacora', ['tabla' => 'proveedores', 'accion' => 'CREAR']);
    }

    public function test_nombre_obligatorio_y_nit_unico_pero_opcional(): void
    {
        $admin = $this->admin();
        Proveedor::create(['nombre' => 'Uno', 'nit' => '111']);

        $this->actingAs($admin)->post(route('proveedores.store'), ['nombre' => ''])
            ->assertSessionHasErrors('nombre');
        $this->actingAs($admin)->post(route('proveedores.store'), ['nombre' => 'Dos', 'nit' => '111'])
            ->assertSessionHasErrors('nit');

        // Varios proveedores sin NIT son válidos
        $this->actingAs($admin)->post(route('proveedores.store'), ['nombre' => 'Sin NIT A'])->assertSessionHasNoErrors();
        $this->actingAs($admin)->post(route('proveedores.store'), ['nombre' => 'Sin NIT B'])->assertSessionHasNoErrors();
    }

    public function test_editar_proveedor_conserva_su_propio_nit(): void
    {
        $proveedor = Proveedor::create(['nombre' => 'Uno', 'nit' => '111']);

        $this->actingAs($this->admin())
            ->put(route('proveedores.update', $proveedor), ['nombre' => 'Uno SRL', 'nit' => '111', 'activo' => 1])
            ->assertSessionHasNoErrors();

        $this->assertEquals('Uno SRL', $proveedor->fresh()->nombre);
    }

    public function test_busqueda_por_nombre_o_nit(): void
    {
        Proveedor::create(['nombre' => 'Lácteos del Valle', 'nit' => '555']);
        Proveedor::create(['nombre' => 'Granos Bolivia', 'nit' => '999']);
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('proveedores.index', ['buscar' => 'Lácteos']))
            ->assertSee('Lácteos del Valle')->assertDontSee('Granos Bolivia');
        $this->actingAs($admin)->get(route('proveedores.index', ['buscar' => '999']))
            ->assertSee('Granos Bolivia')->assertDontSee('Lácteos del Valle');
    }

    public function test_eliminar_es_baja_logica(): void
    {
        $proveedor = Proveedor::create(['nombre' => 'Uno', 'nit' => '111']);

        $this->actingAs($this->admin())
            ->delete(route('proveedores.destroy', $proveedor))
            ->assertRedirect(route('proveedores.index'));

        $this->assertDatabaseHas('proveedores', ['id' => $proveedor->id, 'activo' => false]);
        $this->assertDatabaseHas('bitacora', ['tabla' => 'proveedores', 'accion' => 'MODIFICAR', 'registro_id' => $proveedor->id]);
    }

    public function test_vendedor_recibe_403(): void
    {
        $vendedor = $this->vendedor();
        $proveedor = Proveedor::create(['nombre' => 'Uno']);

        $this->actingAs($vendedor)->get(route('proveedores.index'))->assertForbidden();
        $this->actingAs($vendedor)->post(route('proveedores.store'), ['nombre' => 'X'])->assertForbidden();
        $this->actingAs($vendedor)->delete(route('proveedores.destroy', $proveedor))->assertForbidden();
    }
}
