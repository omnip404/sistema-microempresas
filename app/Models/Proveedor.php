<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Proveedor extends Model
{
    protected $table = 'proveedores';
    protected $fillable = ['nombre', 'nit', 'telefono', 'direccion', 'activo'];

    public function compras(): HasMany
    {
        return $this->hasMany(Compra::class);
    }
}
