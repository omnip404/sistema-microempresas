<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Bitacora extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'bitacora';
    protected $fillable = ['usuario_id', 'accion', 'tabla', 'registro_id', 'detalle', 'ip'];

    protected function casts(): array
    {
        return ['detalle' => 'array'];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class);
    }
}
