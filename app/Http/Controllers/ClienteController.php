<?php

namespace App\Http\Controllers;

use App\Http\Requests\ClienteRequest;
use App\Models\Cliente;
use Illuminate\Http\Request;

/**
 * Gestión de clientes (RF05). Administrador y Vendedor listan, crean y editan;
 * solo el Administrador elimina (ver routes/web.php).
 */
class ClienteController extends Controller
{
    public function index(Request $request)
    {
        $request->validate(['buscar' => ['nullable', 'string', 'max:100']]);

        $clientes = Cliente::query()
            ->when($request->buscar, fn ($q, $texto) => $q->where(
                fn ($q) => $q->where('nombre', 'like', "%{$texto}%")->orWhere('ci_nit', 'like', "%{$texto}%")
            ))
            ->orderBy('nombre')
            ->paginate(15)
            ->withQueryString();

        return view('clientes.index', ['clientes' => $clientes]);
    }

    public function create()
    {
        return view('clientes.form', ['cliente' => new Cliente]);
    }

    public function store(ClienteRequest $request)
    {
        Cliente::create($request->validated());

        return redirect()->route('clientes.index')->with('ok', 'Cliente registrado.');
    }

    public function edit(Cliente $cliente)
    {
        return view('clientes.form', ['cliente' => $cliente]);
    }

    public function update(ClienteRequest $request, Cliente $cliente)
    {
        $cliente->update($request->validated());

        return redirect()->route('clientes.index')->with('ok', 'Cliente actualizado.');
    }

    /** Solo se elimina si no tiene ventas: las ventas conservan su cliente. */
    public function destroy(Cliente $cliente)
    {
        if ($cliente->ventas()->exists()) {
            return redirect()->route('clientes.index')
                ->withErrors(['cliente' => "No se puede eliminar al cliente «{$cliente->nombre}» porque tiene ventas registradas."]);
        }

        $cliente->delete();

        return redirect()->route('clientes.index')->with('ok', 'Cliente eliminado.');
    }
}
