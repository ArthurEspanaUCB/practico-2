<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración para crear la tabla pivot 'rol_permiso'
 *
 * Tabla pivot para la relación muchos a muchos entre roles y permisos.
 *
 * @created 2026-01-06
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rol_permiso', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rol_id')
                ->constrained('roles')
                ->onDelete('cascade');
            $table->foreignId('permiso_id')
                ->constrained('permisos')
                ->onDelete('cascade');
            $table->timestamps();

            $table->unique(['rol_id', 'permiso_id']);
            $table->index('rol_id');
            $table->index('permiso_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rol_permiso');
    }
};
