<?php

namespace App\Http\Controllers;

use App\Http\Requests\CategoriaRequest;
use App\Models\Categoria;

/**
 * Gestión de categorías de productos (RF03). Solo Administrador.
 */
class CategoriaController extends Controller
{
    public function index()
    {
        return view('categorias.index', [
            'categorias' => Categoria::withCount('productos')->orderBy('nombre')->paginate(15),
        ]);
    }

    public function create()
    {
        return view('categorias.form', ['categoria' => new Categoria]);
    }

    public function store(CategoriaRequest $request)
    {
        Categoria::create($request->validated());

        return redirect()->route('categorias.index')->with('ok', 'Categoría registrada.');
    }

    public function edit(Categoria $categoria)
    {
        return view('categorias.form', ['categoria' => $categoria]);
    }

    public function update(CategoriaRequest $request, Categoria $categoria)
    {
        $categoria->update($request->validated());

        return redirect()->route('categorias.index')->with('ok', 'Categoría actualizada.');
    }

    /** Solo se elimina si no tiene productos: los productos no pueden quedar sin categoría. */
    public function destroy(Categoria $categoria)
    {
        if ($categoria->productos()->exists()) {
            return redirect()->route('categorias.index')
                ->withErrors(['categoria' => "No se puede eliminar la categoría «{$categoria->nombre}» porque tiene productos asociados. Reasigne esos productos a otra categoría primero."]);
        }

        $categoria->delete();

        return redirect()->route('categorias.index')->with('ok', 'Categoría eliminada.');
    }
}
