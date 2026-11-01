<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Módulo Seguridad (RF01, RF02): roles y usuarios del sistema.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->tinyIncrements('id');
            $table->string('nombre', 30)->unique();
            $table->string('descripcion', 150)->nullable();
            $table->timestamps();
        });

        Schema::create('usuarios', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('rol_id');
            $table->string('nombre', 100);
            $table->string('email', 120)->unique();
            $table->string('password');
            $table->boolean('activo')->default(true);
            $table->unsignedTinyInteger('intentos_fallidos')->default(0);
            $table->dateTime('bloqueado_hasta')->nullable();
            $table->rememberToken();
            $table->timestamps();

            $table->foreign('rol_id')->references('id')->on('roles');
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('usuarios');
        Schema::dropIfExists('roles');
    }
};
