<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Módulos Ventas (RF06) y Compras (RF07).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ventas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('usuarios');
            $table->foreignId('cliente_id')->nullable()->constrained('clientes')->nullOnDelete();
            $table->dateTime('fecha')->useCurrent()->index();
            $table->decimal('total', 12, 2)->default(0);
            $table->enum('estado', ['REGISTRADA', 'ANULADA'])->default('REGISTRADA');
            $table->string('motivo_anulacion', 200)->nullable();
            $table->timestamps();
        });

        Schema::create('detalle_ventas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venta_id')->constrained('ventas')->cascadeOnDelete();
            $table->foreignId('producto_id')->constrained('productos');
            $table->unsignedInteger('cantidad');
            $table->decimal('precio_unitario', 10, 2);
            $table->unique(['venta_id', 'producto_id']);
        });

        Schema::create('compras', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('usuarios');
            $table->foreignId('proveedor_id')->constrained('proveedores');
            $table->dateTime('fecha')->useCurrent()->index();
            $table->string('nro_comprobante', 30)->nullable();
            $table->decimal('total', 12, 2)->default(0);
            $table->enum('estado', ['REGISTRADA', 'ANULADA'])->default('REGISTRADA');
            $table->timestamps();
        });

        Schema::create('detalle_compras', function (Blueprint $table) {
            $table->id();
            $table->foreignId('compra_id')->constrained('compras')->cascadeOnDelete();
            $table->foreignId('producto_id')->constrained('productos');
            $table->unsignedInteger('cantidad');
            $table->decimal('costo_unitario', 10, 2);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detalle_compras');
        Schema::dropIfExists('compras');
        Schema::dropIfExists('detalle_ventas');
        Schema::dropIfExists('ventas');
    }
};
