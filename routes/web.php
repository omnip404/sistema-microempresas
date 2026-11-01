<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\PanelController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\ProveedorController;
use App\Http\Controllers\VentaController;
use App\Models\Rol;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:10,1');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
    Route::redirect('/', '/ventas/nueva');

    // Administrador y Vendedor
    Route::get('/ventas/nueva', [VentaController::class, 'create'])->name('ventas.create');
    Route::post('/ventas', [VentaController::class, 'store'])->name('ventas.store');

    Route::resource('clientes', ClienteController::class)->except(['show', 'destroy']);

    // Solo Administrador
    Route::middleware('rol:'.Rol::ADMINISTRADOR)->group(function () {
        Route::get('/panel', [PanelController::class, 'index'])->name('panel');
        Route::resource('productos', ProductoController::class)->except('show');
        Route::delete('/clientes/{cliente}', [ClienteController::class, 'destroy'])->name('clientes.destroy');
        Route::resource('categorias', CategoriaController::class)->except('show');
        Route::resource('proveedores', ProveedorController::class)->except('show')
            ->parameters(['proveedores' => 'proveedor']);
        Route::get('/ventas', [VentaController::class, 'index'])->name('ventas.index');
        Route::post('/ventas/{venta}/anular', [VentaController::class, 'anular'])->name('ventas.anular');
    });
});
