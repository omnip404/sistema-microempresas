<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Usuario;
use App\Models\Venta;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClienteTest extends TestCase
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

    public function test_administrador_y_vendedor_crean_y_editan_clientes(): void
    {
        foreach ([$this->admin(), $this->vendedor()] as $i => $usuario) {
            $this->actingAs($usuario)
                ->post(route('clientes.store'), ['nombre' => "Cliente $i", 'ci_nit' => "900$i", 'telefono' => '70000000'])
                ->assertRedirect(route('clientes.index'));

            $cliente = Cliente::where('nombre', "Cliente $i")->first();
            $this->assertNotNull($cliente);

            $this->actingAs($usuario)
                ->put(route('clientes.update', $cliente), ['nombre' => "Cliente $i editado"])
                ->assertRedirect(route('clientes.index'));
            $this->assertEquals("Cliente $i editado", $cliente->fresh()->nombre);
        }
    }

    public function test_nombre_es_obligatorio(): void
    {
        $this->actingAs($this->vendedor())
            ->post(route('clientes.store'), ['nombre' => '', 'ci_nit' => '123'])
            ->assertSessionHasErrors('nombre');
    }

    public function test_busqueda_por_nombre_o_ci_nit(): void
    {
        Cliente::create(['nombre' => 'María Quispe', 'ci_nit' => '7654321']);
        Cliente::create(['nombre' => 'Pedro Mamani', 'ci_nit' => '1111111']);
        $vendedor = $this->vendedor();

        $this->actingAs($vendedor)->get(route('clientes.index', ['buscar' => 'Quispe']))
            ->assertSee('María Quispe')->assertDontSee('Pedro Mamani');
        $this->actingAs($vendedor)->get(route('clientes.index', ['buscar' => '1111111']))
            ->assertSee('Pedro Mamani')->assertDontSee('María Quispe');
    }

    public function test_administrador_elimina_cliente_sin_ventas(): void
    {
        $cliente = Cliente::create(['nombre' => 'Sin ventas']);

        $this->actingAs($this->admin())
            ->delete(route('clientes.destroy', $cliente))
            ->assertRedirect(route('clientes.index'));

        $this->assertDatabaseMissing('clientes', ['id' => $cliente->id]);
    }

    public function test_no_se_elimina_cliente_con_ventas(): void
    {
        $cliente = Cliente::create(['nombre' => 'Con ventas']);
        $admin = $this->admin();
        Venta::create(['usuario_id' => $admin->id, 'cliente_id' => $cliente->id, 'total' => 10, 'estado' => 'REGISTRADA']);

        $this->actingAs($admin)
            ->delete(route('clientes.destroy', $cliente))
            ->assertSessionHasErrors('cliente');

        $this->assertDatabaseHas('clientes', ['id' => $cliente->id]);
    }

    public function test_vendedor_no_puede_eliminar(): void
    {
        $cliente = Cliente::create(['nombre' => 'Sin ventas']);

        $this->actingAs($this->vendedor())
            ->delete(route('clientes.destroy', $cliente))
            ->assertForbidden();

        $this->assertDatabaseHas('clientes', ['id' => $cliente->id]);
    }

    public function test_menu_muestra_clientes_a_ambos_roles(): void
    {
        $this->actingAs($this->admin())->get(route('clientes.index'))->assertOk()->assertSee('Nuevo cliente');
        $this->actingAs($this->vendedor())->get(route('clientes.index'))
            ->assertOk()
            ->assertSee(route('clientes.index'), false);
    }
}
