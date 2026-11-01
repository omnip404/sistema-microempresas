<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovimientoInventario extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'movimientos_inventario';
    protected $fillable = [
        'producto_id', 'usuario_id', 'tipo', 'cantidad',
        'stock_resultante', 'referencia', 'observacion',
    ];

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }
}
