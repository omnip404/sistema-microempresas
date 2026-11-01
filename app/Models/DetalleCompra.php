<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetalleCompra extends Model
{
    public $timestamps = false;

    protected $table = 'detalle_compras';

    protected $fillable = ['compra_id', 'producto_id', 'cantidad', 'costo_unitario'];

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }
}
