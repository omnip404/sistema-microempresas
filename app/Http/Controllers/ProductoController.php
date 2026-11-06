<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductoRequest;
use App\Models\Categoria;
use App\Models\Producto;
use Illuminate\Http\Request;

/**
 * Gestión de productos (RF03). El stock no se edita aquí: solo cambia con
 * ventas, compras o ajustes de inventario, para que quede trazado.
 */
class ProductoController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'buscar' => ['nullable', 'string', 'max:100'],
            'categoria_id' => ['nullable', 'integer'],
        ]);

        $productos = Producto::with('categoria')
            ->when($request->buscar, function ($q, $texto) {
                // % y _ se buscan como texto, no como comodines. «!» como escape funciona igual en MySQL y SQLite.
                $patron = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $texto).'%';

                $q->where(fn ($q) => $q->whereRaw("nombre LIKE ? ESCAPE '!'", [$patron])
                    ->orWhereRaw("codigo LIKE ? ESCAPE '!'", [$patron]));
            })
            ->when($request->categoria_id, fn ($q, $cat) => $q->where('categoria_id', $cat))
            ->orderBy('nombre')
            ->paginate(15)
            ->withQueryString();

        return view('productos.index', [
            'productos' => $productos,
            'categorias' => Categoria::orderBy('nombre')->get(),
        ]);
    }

    public function create()
    {
        return view('productos.form', [
            'producto' => new Producto(['unidad_medida' => 'unidad', 'stock_minimo' => 5, 'activo' => true]),
            'categorias' => Categoria::orderBy('nombre')->get(),
        ]);
    }

    public function store(ProductoRequest $request)
    {
        Producto::create($request->validated());

        return redirect()->route('productos.index')->with('ok', 'Producto registrado.');
    }

    public function edit(Producto $producto)
    {
        return view('productos.form', [
            'producto' => $producto,
            'categorias' => Categoria::orderBy('nombre')->get(),
        ]);
    }

    public function update(ProductoRequest $request, Producto $producto)
    {
        $producto->update($request->validated());

        return redirect()->route('productos.index')->with('ok', 'Producto actualizado.');
    }

    /** Baja lógica: el producto conserva su historial de ventas. */
    public function destroy(Producto $producto)
    {
        $producto->update(['activo' => false]);

        return redirect()->route('productos.index')->with('ok', 'Producto dado de baja.');
    }
}
