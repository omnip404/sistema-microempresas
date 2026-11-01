<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetalleVenta extends Model
{
    public $timestamps = false;

    protected $table = 'detalle_ventas';

    protected $fillable = ['venta_id', 'producto_id', 'cantidad', 'precio_unitario'];

    protected function casts(): array
    {
        return ['precio_unitario' => 'decimal:2'];
    }

    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class);
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    public function subtotal(): float
    {
        return $this->cantidad * (float) $this->precio_unitario;
    }
}
