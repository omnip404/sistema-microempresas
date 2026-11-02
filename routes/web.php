<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\BitacoraController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\ComparacionController;
use App\Http\Controllers\CompraController;
use App\Http\Controllers\InventarioController;
use App\Http\Controllers\PanelController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\ProveedorController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\VentaController;
use App\Models\Rol;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:10,1');
});

Route::middleware(['auth', 'activo'])->group(function () {
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
        Route::get('/productos/{producto}/movimientos', [InventarioController::class, 'movimientos'])->name('inventario.movimientos');
        Route::get('/productos/{producto}/ajuste', [InventarioController::class, 'ajuste'])->name('inventario.ajuste');
        Route::post('/productos/{producto}/ajuste', [InventarioController::class, 'ajustar'])->name('inventario.ajustar');
        Route::resource('usuarios', UsuarioController::class)->except(['show', 'destroy']);
        Route::post('/usuarios/{usuario}/estado', [UsuarioController::class, 'cambiarEstado'])->name('usuarios.estado');
        Route::post('/usuarios/{usuario}/desbloquear', [UsuarioController::class, 'desbloquear'])->name('usuarios.desbloquear');
        Route::get('/reportes', [ReporteController::class, 'index'])->name('reportes.index');
        Route::get('/reportes/exportar', [ReporteController::class, 'exportar'])->name('reportes.exportar');
        Route::get('/comparar-periodos', [ComparacionController::class, 'index'])->name('comparacion.index');
        Route::get('/bitacora', [BitacoraController::class, 'index'])->name('bitacora.index');
        Route::get('/compras', [CompraController::class, 'index'])->name('compras.index');
        Route::get('/compras/nueva', [CompraController::class, 'create'])->name('compras.create');
        Route::post('/compras', [CompraController::class, 'store'])->name('compras.store');
        Route::post('/compras/{compra}/anular', [CompraController::class, 'anular'])->name('compras.anular');
        Route::delete('/clientes/{cliente}', [ClienteController::class, 'destroy'])->name('clientes.destroy');
        Route::resource('categorias', CategoriaController::class)->except('show');
        Route::resource('proveedores', ProveedorController::class)->except('show')
            ->parameters(['proveedores' => 'proveedor']);
        Route::get('/ventas', [VentaController::class, 'index'])->name('ventas.index');
        Route::post('/ventas/{venta}/anular', [VentaController::class, 'anular'])->name('ventas.anular');
    });
});
