<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Módulo Catálogo (RF03, RF04, RF05): categorías, productos, proveedores y clientes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categorias', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 60)->unique();
            $table->string('descripcion', 150)->nullable();
            $table->timestamps();
        });

        Schema::create('productos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('categoria_id')->constrained('categorias');
            $table->string('codigo', 30)->unique();
            $table->string('nombre', 120)->index();
            $table->string('unidad_medida', 20)->default('unidad');
            $table->decimal('precio_compra', 10, 2)->default(0);
            $table->decimal('precio_venta', 10, 2);
            $table->unsignedInteger('stock')->default(0);
            $table->unsignedInteger('stock_minimo')->default(5);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('proveedores', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 120);
            $table->string('nit', 20)->nullable()->unique();
            $table->string('telefono', 20)->nullable();
            $table->string('direccion', 150)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('clientes', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 120);
            $table->string('ci_nit', 20)->nullable()->index();
            $table->string('telefono', 20)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clientes');
        Schema::dropIfExists('proveedores');
        Schema::dropIfExists('productos');
        Schema::dropIfExists('categorias');
    }
};
