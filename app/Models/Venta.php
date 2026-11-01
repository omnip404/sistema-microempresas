<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Venta extends Model
{
    public const REGISTRADA = 'REGISTRADA';

    public const ANULADA = 'ANULADA';

    protected $table = 'ventas';

    protected $fillable = ['usuario_id', 'cliente_id', 'fecha', 'total', 'estado', 'motivo_anulacion'];

    protected function casts(): array
    {
        return ['fecha' => 'datetime', 'total' => 'decimal:2'];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class);
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(DetalleVenta::class);
    }
}
