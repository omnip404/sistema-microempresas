<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Usuario;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoriaTest extends TestCase
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

    public function test_administrador_crea_categoria_y_queda_en_bitacora(): void
    {
        $this->actingAs($this->admin())
            ->post(route('categorias.store'), ['nombre' => 'Ferretería', 'descripcion' => 'Herramientas y tornillos'])
            ->assertRedirect(route('categorias.index'));

        $this->assertDatabaseHas('categorias', ['nombre' => 'Ferretería']);
        $this->assertDatabaseHas('bitacora', ['tabla' => 'categorias', 'accion' => 'CREAR']);
    }

    public function test_listado_muestra_cuantos_productos_tiene_cada_categoria(): void
    {
        $categoria = Categoria::create(['nombre' => 'Categoría de prueba']);
        $producto = Categoria::has('productos')->first()->productos()->first();
        $producto->update(['categoria_id' => $categoria->id]);

        $this->actingAs($this->admin())
            ->get(route('categorias.index'))
            ->assertOk()
            ->assertSee('Categoría de prueba')
            ->assertSee('<td class="numero">1</td>', false);
    }

    public function test_nombre_es_obligatorio_unico_y_de_maximo_60(): void
    {
        $existente = Categoria::first();
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('categorias.store'), ['nombre' => ''])
            ->assertSessionHasErrors('nombre');
        $this->actingAs($admin)->post(route('categorias.store'), ['nombre' => $existente->nombre])
            ->assertSessionHasErrors('nombre');
        $this->actingAs($admin)->post(route('categorias.store'), ['nombre' => str_repeat('a', 61)])
            ->assertSessionHasErrors('nombre');
    }

    public function test_editar_conserva_su_propio_nombre(): void
    {
        $categoria = Categoria::first();

        $this->actingAs($this->admin())
            ->put(route('categorias.update', $categoria), ['nombre' => $categoria->nombre, 'descripcion' => 'Nueva descripción'])
            ->assertSessionHasNoErrors();

        $this->assertEquals('Nueva descripción', $categoria->fresh()->descripcion);
    }

    public function test_no_se_elimina_categoria_con_productos(): void
    {
        $categoria = Categoria::has('productos')->first();

        $this->actingAs($this->admin())
            ->delete(route('categorias.destroy', $categoria))
            ->assertRedirect(route('categorias.index'))
            ->assertSessionHasErrors('categoria');

        $this->assertDatabaseHas('categorias', ['id' => $categoria->id]);
    }

    public function test_se_elimina_categoria_sin_productos(): void
    {
        $categoria = Categoria::create(['nombre' => 'Vacía']);

        $this->actingAs($this->admin())
            ->delete(route('categorias.destroy', $categoria))
            ->assertRedirect(route('categorias.index'));

        $this->assertDatabaseMissing('categorias', ['id' => $categoria->id]);
    }

    public function test_vendedor_recibe_403(): void
    {
        $vendedor = $this->vendedor();
        $categoria = Categoria::first();

        $this->actingAs($vendedor)->get(route('categorias.index'))->assertForbidden();
        $this->actingAs($vendedor)->post(route('categorias.store'), ['nombre' => 'X'])->assertForbidden();
        $this->actingAs($vendedor)->delete(route('categorias.destroy', $categoria))->assertForbidden();
    }
}
