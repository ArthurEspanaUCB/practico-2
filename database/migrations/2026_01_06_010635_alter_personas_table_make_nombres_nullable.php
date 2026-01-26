<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración para hacer nullable la columna 'nombres' en tabla personas
 *
 * Razón: Para proveedores/clientes con tipo de documento RUC (empresas),
 * no se requiere nombres personales ya que usan razon_social.
 * Solo las personas naturales (DNI, CE, PASAPORTE) necesitan nombres.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('personas', function (Blueprint $table) {
            // Hacer nullable la columna nombres para permitir empresas
            $table->string('nombres', 100)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('personas', function (Blueprint $table) {
            // Revertir a NOT NULL (solo si no hay valores nulos)
            $table->string('nombres', 100)->nullable(false)->change();
        });
    }
};
