<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración para crear la tabla 'permisos'
 *
 * Esta tabla almacena los permisos del sistema.
 * Un permiso representa una acción específica sobre un módulo.
 *
 * @created 2026-01-06
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permisos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 100)->unique();
            $table->string('nombre', 150);
            $table->string('modulo', 50);
            $table->text('descripcion')->nullable();
            $table->timestamps();

            $table->index('codigo');
            $table->index('modulo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permisos');
    }
};
