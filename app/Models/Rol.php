<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rol extends Model
{
    public const ADMINISTRADOR = 'Administrador';
    public const VENDEDOR = 'Vendedor';

    protected $table = 'roles';
    protected $fillable = ['nombre', 'descripcion'];

    public function usuarios(): HasMany
    {
        return $this->hasMany(Usuario::class);
    }
}
