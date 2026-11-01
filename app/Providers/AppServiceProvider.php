<?php

namespace App\Providers;

use App\Models\Categoria;
use App\Models\Compra;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\Usuario;
use App\Models\Venta;
use App\Observers\BitacoraObserver;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Bitácora automática (RF12) sobre las entidades críticas
        foreach ([Producto::class, Categoria::class, Proveedor::class, Venta::class, Compra::class, Usuario::class] as $modelo) {
            $modelo::observe(BitacoraObserver::class);
        }

        Paginator::useBootstrapFive();
    }
}
