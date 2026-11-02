<?php

namespace App\Services;

use App\Models\MovimientoInventario;
use App\Models\Producto;
use App\Models\Usuario;
use App\Models\Venta;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Reglas de negocio del módulo Ventas (RF06).
 * Registrar o anular una venta afecta varias tablas, por eso todo ocurre
 * dentro de una transacción: o se guarda completo o no se guarda nada.
 */
class VentaService
{
    /**
     * @param  array<int, array{producto_id:int, cantidad:int}>  $items
     */
    public function registrar(Usuario $vendedor, array $items, ?int $clienteId = null): Venta
    {
        return DB::transaction(function () use ($vendedor, $items, $clienteId) {
            $venta = Venta::create([
                'usuario_id' => $vendedor->id,
                'cliente_id' => $clienteId,
                'fecha' => now(),
                'total' => 0,
            ]);

            $total = 0;

            foreach ($items as $item) {
                // Bloquea la fila para que dos ventas simultáneas no vendan el mismo stock.
                $producto = Producto::whereKey($item['producto_id'])->lockForUpdate()->firstOrFail();

                if (! $producto->activo) {
                    throw ValidationException::withMessages([
                        'items' => "El producto {$producto->nombre} está dado de baja y no se puede vender.",
                    ]);
                }

                if ($item['cantidad'] > $producto->stock) {
                    throw ValidationException::withMessages([
                        'items' => "Stock insuficiente de {$producto->nombre}: disponible {$producto->stock}.",
                    ]);
                }

                $venta->detalles()->create([
                    'producto_id' => $producto->id,
                    'cantidad' => $item['cantidad'],
                    'precio_unitario' => $producto->precio_venta,
                ]);

                $producto->decrement('stock', $item['cantidad']);

                MovimientoInventario::create([
                    'producto_id' => $producto->id,
                    'usuario_id' => $vendedor->id,
                    'tipo' => 'SALIDA',
                    'cantidad' => -$item['cantidad'],
                    'stock_resultante' => $producto->stock,
                    'referencia' => "VENTA-{$venta->id}",
                ]);

                $total += $item['cantidad'] * (float) $producto->precio_venta;
            }

            $venta->update(['total' => $total]);

            return $venta->load('detalles.producto');
        });
    }

    /** Anula una venta y devuelve las unidades al inventario (solo Administrador). */
    public function anular(Venta $venta, string $motivo, Usuario $admin): Venta
    {
        if ($venta->estado === Venta::ANULADA) {
            throw ValidationException::withMessages(['venta' => 'La venta ya fue anulada.']);
        }

        return DB::transaction(function () use ($venta, $motivo, $admin) {
            foreach ($venta->detalles as $detalle) {
                $producto = Producto::whereKey($detalle->producto_id)->lockForUpdate()->firstOrFail();
                $producto->increment('stock', $detalle->cantidad);

                MovimientoInventario::create([
                    'producto_id' => $producto->id,
                    'usuario_id' => $admin->id,
                    'tipo' => 'ENTRADA',
                    'cantidad' => $detalle->cantidad,
                    'stock_resultante' => $producto->stock,
                    'referencia' => "ANULA-VENTA-{$venta->id}",
                ]);
            }

            $venta->update(['estado' => Venta::ANULADA, 'motivo_anulacion' => $motivo]);

            return $venta;
        });
    }
}
