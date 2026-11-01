<?php

namespace App\Services;

use App\Models\MovimientoInventario;
use App\Models\Producto;
use App\Models\Usuario;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Ajustes manuales de inventario (RF08): correcciones por conteo físico, mermas, etc.
 * Cada ajuste deja un movimiento AJUSTE con su observación.
 */
class InventarioService
{
    /**
     * @param  int  $cantidad  unidades a sumar (positivo) o restar (negativo); no puede ser 0
     */
    public function ajustar(Producto $producto, Usuario $usuario, int $cantidad, string $observacion): MovimientoInventario
    {
        if ($cantidad === 0) {
            throw ValidationException::withMessages(['cantidad' => 'La cantidad del ajuste no puede ser cero.']);
        }

        return DB::transaction(function () use ($producto, $usuario, $cantidad, $observacion) {
            // Bloquea la fila para que una venta simultánea no deje el stock negativo.
            $producto = Producto::whereKey($producto->id)->lockForUpdate()->firstOrFail();

            if ($producto->stock + $cantidad < 0) {
                throw ValidationException::withMessages([
                    'cantidad' => 'No se pueden restar '.abs($cantidad)." unidades de {$producto->nombre}: el stock actual es {$producto->stock} y no puede quedar negativo.",
                ]);
            }

            $producto->increment('stock', $cantidad);

            return MovimientoInventario::create([
                'producto_id' => $producto->id,
                'usuario_id' => $usuario->id,
                'tipo' => 'AJUSTE',
                'cantidad' => $cantidad,
                'stock_resultante' => $producto->stock,
                'referencia' => 'AJUSTE-MANUAL',
                'observacion' => $observacion,
            ]);
        });
    }
}
