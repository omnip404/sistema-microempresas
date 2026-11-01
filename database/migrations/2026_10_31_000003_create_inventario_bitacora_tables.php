<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Módulos Inventario (RF08) y Bitácora (RF12).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movimientos_inventario', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained('productos');
            $table->foreignId('usuario_id')->constrained('usuarios');
            $table->enum('tipo', ['ENTRADA', 'SALIDA', 'AJUSTE']);
            $table->integer('cantidad');
            $table->unsignedInteger('stock_resultante');
            $table->string('referencia', 50)->nullable();
            $table->string('observacion', 200)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['producto_id', 'created_at']);
        });

        Schema::create('bitacora', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->string('accion', 30);
            $table->string('tabla', 40);
            $table->unsignedBigInteger('registro_id')->nullable();
            $table->json('detalle')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bitacora');
        Schema::dropIfExists('movimientos_inventario');
    }
};
