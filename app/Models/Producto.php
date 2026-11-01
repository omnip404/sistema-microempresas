<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Producto extends Model
{
    protected $table = 'productos';
    protected $fillable = [
        'categoria_id', 'codigo', 'nombre', 'unidad_medida',
        'precio_compra', 'precio_venta', 'stock', 'stock_minimo', 'activo',
    ];

    protected function casts(): array
    {
        return [
            'precio_compra' => 'decimal:2',
            'precio_venta' => 'decimal:2',
            'activo' => 'boolean',
        ];
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class);
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(MovimientoInventario::class);
    }

    /** Productos activos cuyo stock llegó al mínimo (RF08). */
    public function scopeStockBajo(Builder $query): Builder
    {
        return $query->where('activo', true)->whereColumn('stock', '<=', 'stock_minimo');
    }
}
