<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Compra extends Model
{
    public const REGISTRADA = 'REGISTRADA';

    public const ANULADA = 'ANULADA';

    protected $table = 'compras';

    protected $fillable = ['usuario_id', 'proveedor_id', 'fecha', 'nro_comprobante', 'total', 'estado'];

    protected function casts(): array
    {
        return ['fecha' => 'datetime', 'total' => 'decimal:2'];
    }

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class);
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(DetalleCompra::class);
    }
}
