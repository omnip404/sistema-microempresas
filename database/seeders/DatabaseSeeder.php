<?php

namespace Database\Seeders;

use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Datos de prueba de una microempresa tipo (tienda de abarrotes de El Alto).
 * Usuarios de prueba: admin@microempresa.test y vendedor@microempresa.test,
 * contraseña "password" (solo fuera de producción: en producción se usa SEED_PASSWORD o una
 * contraseña aleatoria que se muestra una vez en la consola).
 */
class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $admin = Rol::create(['nombre' => Rol::ADMINISTRADOR, 'descripcion' => 'Acceso total: catálogo, compras, reportes, usuarios y bitácora']);
        $vendedor = Rol::create(['nombre' => Rol::VENDEDOR, 'descripcion' => 'Registra ventas y consulta productos y stock']);

        $clave = $this->claveInicial();

        Usuario::create(['rol_id' => $admin->id, 'nombre' => 'Administrador', 'email' => 'admin@microempresa.test', 'password' => $clave]);
        Usuario::create(['rol_id' => $vendedor->id, 'nombre' => 'Vendedor', 'email' => 'vendedor@microempresa.test', 'password' => $clave]);

        $categorias = collect(['Abarrotes', 'Lácteos', 'Limpieza', 'Higiene personal'])
            ->mapWithKeys(fn ($nombre) => [$nombre => Categoria::create(['nombre' => $nombre])->id]);

        $productos = [
            ['ABA-001', 'Arroz Grano de Oro 5 kg', 'Abarrotes', 'bolsa', 48.00, 55.00, 20],
            ['ABA-002', 'Aceite Fino 900 ml', 'Abarrotes', 'botella', 14.50, 17.00, 30],
            ['ABA-003', 'Azúcar Guabirá 1 kg', 'Abarrotes', 'bolsa', 6.80, 8.00, 40],
            ['ABA-004', 'Fideo Lazzaroni 400 g', 'Abarrotes', 'paquete', 5.20, 6.50, 35],
            ['LAC-001', 'Leche PIL 1 L', 'Lácteos', 'unidad', 6.00, 7.00, 4],
            ['LIM-001', 'Detergente Ola 1 kg', 'Limpieza', 'bolsa', 15.00, 18.00, 12],
            ['HIG-001', 'Papel higiénico Scott x4', 'Higiene personal', 'paquete', 9.50, 12.00, 3],
        ];

        foreach ($productos as [$codigo, $nombre, $categoria, $unidad, $compra, $venta, $stock]) {
            Producto::create([
                'categoria_id' => $categorias[$categoria],
                'codigo' => $codigo,
                'nombre' => $nombre,
                'unidad_medida' => $unidad,
                'precio_compra' => $compra,
                'precio_venta' => $venta,
                'stock' => $stock,
                'stock_minimo' => 5,
            ]);
        }

        Proveedor::create(['nombre' => 'Distribuidora mayorista (ejemplo)', 'telefono' => '70000000']);
        Cliente::create(['nombre' => 'Cliente frecuente (ejemplo)', 'ci_nit' => '1234567']);
    }

    /** La contraseña publicada en el README solo vale fuera de producción. */
    private function claveInicial(): string
    {
        if (! app()->isProduction()) {
            return 'password';
        }

        $clave = env('SEED_PASSWORD');

        if (blank($clave)) {
            $clave = Str::random(20);
            $this->command?->warn("Contraseña inicial de las cuentas semilla (se muestra una sola vez): {$clave}");
        }

        return $clave;
    }
}
