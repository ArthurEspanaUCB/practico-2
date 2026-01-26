<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración para crear la tabla 'roles'
 *
 * Esta tabla almacena los roles del sistema.
 * Un rol agrupa permisos que pueden asignarse a usuarios.
 *
 * @created 2026-01-06
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->unique();
            $table->string('nombre', 100);
            $table->text('descripcion')->nullable();
            $table->boolean('estado')->default(true);
            $table->timestamps();

            $table->index('codigo');
            $table->index('estado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
