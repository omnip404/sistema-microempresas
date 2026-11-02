<?php

namespace Tests\Security;

use App\Models\Categoria;
use App\Models\Producto;
use App\Models\Usuario;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Sec08b: una barra invertida antes de la comilla («\"») no debe permitir cerrar el
 * campo en el CSV exportado y abrir una celda nueva con una fórmula, al leerlo
 * con un lector RFC 4180 (sin carácter de escape).
 */
class Sec08bTest extends TestCase
{
    use RefreshDatabase;

    public function test_barra_invertida_y_comilla_no_parten_la_celda_en_el_csv(): void
    {
        $this->seed(DatabaseSeeder::class);
        Producto::query()->update(['activo' => false]);

        $admin = Usuario::where('email', 'admin@microempresa.test')->first();
        $nombre = 'x\\";=HYPERLINK("http://evil.test","clic")';

        $this->actingAs($admin)->post(route('productos.store'), [
            'categoria_id' => Categoria::first()->id,
            'codigo' => 'S08B',
            'nombre' => $nombre,
            'unidad_medida' => 'unidad',
            'precio_compra' => 1,
            'precio_venta' => 2,
            'stock_minimo' => 0,
            'activo' => 1,
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('productos', ['codigo' => 'S08B', 'nombre' => $nombre]);

        $contenido = $this->actingAs($admin)
            ->get(route('reportes.exportar', ['tipo' => 'inventario']))
            ->assertOk()
            ->streamedContent();

        $lineas = preg_split('/\r?\n/', trim(substr($contenido, 3)));
        $esperadas = count(str_getcsv($lineas[0], ';', '"', ''));
        $this->assertSame(6, $esperadas);

        foreach ($lineas as $linea) {
            $celdas = str_getcsv($linea, ';', '"', '');
            $this->assertCount($esperadas, $celdas, "Fila con columnas de más: $linea");
            // Las celdas posteriores a la primera no deben empezar con una fórmula inyectada
            foreach (array_slice($celdas, 1) as $celda) {
                if ($celda === $nombre) {
                    continue;   // el nombre íntegro en su celda: su saneamiento es cosa de Sec08a
                }
                $this->assertDoesNotMatchRegularExpression('/^[=+\-@]/', $celda, "Celda extra con fórmula: $linea");
            }
        }

        $fila = collect($lineas)->first(fn ($l) => str_starts_with($l, 'S08B'));
        $this->assertNotNull($fila);
        $this->assertSame($nombre, str_getcsv($fila, ';', '"', '')[1]);
    }
}
