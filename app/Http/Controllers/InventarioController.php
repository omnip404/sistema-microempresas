<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Services\InventarioService;
use Illuminate\Http\Request;

/**
 * Ajustes e historial de movimientos de inventario (RF08). Solo Administrador.
 */
class InventarioController extends Controller
{
    public function __construct(private InventarioService $inventario) {}

    public function movimientos(Producto $producto)
    {
        return view('inventario.movimientos', [
            'producto' => $producto,
            'movimientos' => $producto->movimientos()->with('usuario')->latest('created_at')->latest('id')->paginate(20),
        ]);
    }

    public function ajuste(Producto $producto)
    {
        return view('inventario.ajuste', compact('producto'));
    }

    public function ajustar(Request $request, Producto $producto)
    {
        $datos = $request->validate([
            'operacion' => ['required', 'in:sumar,restar'],
            'cantidad' => ['required', 'integer', 'min:1'],
            'observacion' => ['required', 'string', 'max:200'],
        ], [], [
            'operacion' => 'operación',
            'observacion' => 'observación',
        ]);

        $cantidad = $datos['operacion'] === 'restar' ? -(int) $datos['cantidad'] : (int) $datos['cantidad'];

        $this->inventario->ajustar($producto, $request->user(), $cantidad, $datos['observacion']);

        return redirect()->route('inventario.movimientos', $producto)
            ->with('ok', 'Ajuste registrado. Stock actual: '.$producto->fresh()->stock.'.');
    }
}
