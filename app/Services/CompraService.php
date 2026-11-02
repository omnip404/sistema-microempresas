<?php

namespace App\Services;

use App\Models\Compra;
use App\Models\MovimientoInventario;
use App\Models\Producto;
use App\Models\Usuario;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Reglas de negocio del módulo Compras (RF07).
 * Registrar o anular una compra afecta varias tablas, por eso todo ocurre
 * dentro de una transacción, igual que en VentaService.
 */
class CompraService
{
    /**
     * @param  array<int, array{producto_id:int, cantidad:int, costo_unitario:float|int|string}>  $items
     */
    public function registrar(Usuario $usuario, int $proveedorId, array $items, ?string $nroComprobante = null): Compra
    {
        if ($items === []) {
            throw ValidationException::withMessages(['items' => 'Agregue al menos un producto a la compra.']);
        }

        foreach ($items as $item) {
            if ((int) $item['cantidad'] < 1) {
                throw ValidationException::withMessages(['items' => 'La cantidad de cada producto debe ser al menos 1.']);
            }
            if ((float) $item['costo_unitario'] < 0) {
                throw ValidationException::withMessages(['items' => 'El costo unitario no puede ser negativo.']);
            }
        }

        return DB::transaction(function () use ($usuario, $proveedorId, $items, $nroComprobante) {
            $compra = Compra::create([
                'usuario_id' => $usuario->id,
                'proveedor_id' => $proveedorId,
                'fecha' => now(),
                'nro_comprobante' => $nroComprobante,
                'total' => 0,
            ]);

            $total = 0;

            foreach ($items as $item) {
                $producto = Producto::whereKey($item['producto_id'])->lockForUpdate()->firstOrFail();

                if (! $producto->activo) {
                    throw ValidationException::withMessages([
                        'items' => "El producto {$producto->nombre} está dado de baja y no se puede comprar.",
                    ]);
                }

                $compra->detalles()->create([
                    'producto_id' => $producto->id,
                    'cantidad' => $item['cantidad'],
                    'costo_unitario' => $item['costo_unitario'],
                ]);

                $producto->increment('stock', $item['cantidad']);
                $producto->update(['precio_compra' => $item['costo_unitario']]);

                MovimientoInventario::create([
                    'producto_id' => $producto->id,
                    'usuario_id' => $usuario->id,
                    'tipo' => 'ENTRADA',
                    'cantidad' => $item['cantidad'],
                    'stock_resultante' => $producto->stock,
                    'referencia' => "COMPRA-{$compra->id}",
                ]);

                $total += $item['cantidad'] * (float) $item['costo_unitario'];
            }

            $compra->update(['total' => $total]);

            // refresh() trae también el estado, que la base asigna por defecto
            return $compra->refresh()->load('detalles.producto');
        });
    }

    /**
     * Anula una compra y descuenta las unidades que ingresó. Falla si alguno de
     * los productos ya no tiene stock suficiente para revertir la entrada.
     */
    public function anular(Compra $compra, Usuario $admin): Compra
    {
        return DB::transaction(function () use ($compra, $admin) {
            // Se relee la compra bajo bloqueo: dos anulaciones simultáneas no descuentan el stock dos veces
            $compra = Compra::whereKey($compra->id)->lockForUpdate()->firstOrFail();

            if ($compra->estado === Compra::ANULADA) {
                throw ValidationException::withMessages(['compra' => 'La compra ya fue anulada.']);
            }

            $productos = [];

            // Primero se bloquea y verifica todo (sumando si un producto se repite); recién después se descuenta.
            foreach ($compra->detalles->groupBy('producto_id') as $productoId => $detalles) {
                $producto = $productos[$productoId] = Producto::whereKey($productoId)->lockForUpdate()->firstOrFail();
                $ingresadas = $detalles->sum('cantidad');

                if ($ingresadas > $producto->stock) {
                    throw ValidationException::withMessages([
                        'compra' => "No se puede anular la compra: de {$producto->nombre} quedan {$producto->stock} unidades y la compra ingresó {$ingresadas}.",
                    ]);
                }
            }

            foreach ($compra->detalles as $detalle) {
                $producto = $productos[$detalle->producto_id];
                $producto->decrement('stock', $detalle->cantidad);

                MovimientoInventario::create([
                    'producto_id' => $producto->id,
                    'usuario_id' => $admin->id,
                    'tipo' => 'SALIDA',
                    'cantidad' => -$detalle->cantidad,
                    'stock_resultante' => $producto->stock,
                    'referencia' => "ANULA-COMPRA-{$compra->id}",
                ]);
            }

            $compra->update(['estado' => Compra::ANULADA]);

            return $compra;
        });
    }
}
