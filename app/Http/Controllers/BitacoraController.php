<?php

namespace App\Http\Controllers;

use App\Models\Bitacora;
use App\Models\Usuario;
use Illuminate\Http\Request;

/**
 * Consulta de la bitácora del sistema (RF12). Solo lectura, solo Administrador.
 */
class BitacoraController extends Controller
{
    public function index(Request $request)
    {
        $filtros = $request->validate([
            'usuario_id' => ['nullable', 'integer'],
            'tabla' => ['nullable', 'string', 'max:40'],
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date', 'after_or_equal:desde'],
        ], [
            'hasta.after_or_equal' => 'La fecha "hasta" no puede ser anterior a la fecha "desde".',
        ], [
            'usuario_id' => 'usuario',
        ]);

        $registros = Bitacora::with('usuario')
            ->when($filtros['usuario_id'] ?? null, fn ($q, $id) => $q->where('usuario_id', $id))
            ->when($filtros['tabla'] ?? null, fn ($q, $tabla) => $q->where('tabla', $tabla))
            ->when($filtros['desde'] ?? null, fn ($q, $fecha) => $q->whereDate('created_at', '>=', $fecha))
            ->when($filtros['hasta'] ?? null, fn ($q, $fecha) => $q->whereDate('created_at', '<=', $fecha))
            ->latest('created_at')
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        return view('bitacora.index', [
            'registros' => $registros,
            'usuarios' => Usuario::orderBy('nombre')->get(['id', 'nombre']),
            'tablas' => Bitacora::distinct()->orderBy('tabla')->pluck('tabla'),
        ]);
    }
}
