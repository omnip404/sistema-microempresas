<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProveedorRequest;
use App\Models\Proveedor;
use Illuminate\Http\Request;

/**
 * Gestión de proveedores (RF04). Solo Administrador.
 */
class ProveedorController extends Controller
{
    public function index(Request $request)
    {
        $request->validate(['buscar' => ['nullable', 'string', 'max:100']]);

        $proveedores = Proveedor::query()
            ->when($request->buscar, fn ($q, $texto) => $q->where(
                fn ($q) => $q->where('nombre', 'like', "%{$texto}%")->orWhere('nit', 'like', "%{$texto}%")
            ))
            ->orderBy('nombre')
            ->paginate(15)
            ->withQueryString();

        return view('proveedores.index', ['proveedores' => $proveedores]);
    }

    public function create()
    {
        return view('proveedores.form', ['proveedor' => new Proveedor(['activo' => true])]);
    }

    public function store(ProveedorRequest $request)
    {
        Proveedor::create($request->validated());

        return redirect()->route('proveedores.index')->with('ok', 'Proveedor registrado.');
    }

    public function edit(Proveedor $proveedor)
    {
        return view('proveedores.form', ['proveedor' => $proveedor]);
    }

    public function update(ProveedorRequest $request, Proveedor $proveedor)
    {
        $proveedor->update($request->validated());

        return redirect()->route('proveedores.index')->with('ok', 'Proveedor actualizado.');
    }

    /** Baja lógica: el proveedor conserva su historial de compras. */
    public function destroy(Proveedor $proveedor)
    {
        $proveedor->update(['activo' => false]);

        return redirect()->route('proveedores.index')->with('ok', 'Proveedor dado de baja.');
    }
}
