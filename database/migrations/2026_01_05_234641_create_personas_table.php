<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración para crear la tabla 'personas'
 *
 * Esta tabla centraliza la información personal básica de todas las personas
 * que interactúan con el sistema (clientes, proveedores, empleados, etc.).
 * Implementa un patrón de herencia donde otras entidades referencian a esta tabla.
 * Soporta múltiples tipos de documentos de identidad y datos de contacto completos.
 *
 * @created 2026-01-05
 */
return new class extends Migration
{
    /**
     * Ejecuta la migración para crear la tabla personas
     */
    public function up(): void
    {
        Schema::create('personas', function (Blueprint $table) {
            // Clave primaria autoincremental
            $table->id();

            // Tipo de documento de identidad
            // DNI: Documento Nacional de Identidad | RUC: Registro Único de Contribuyentes
            // CE: Carné de Extranjería | PASAPORTE: Pasaporte internacional
            $table->enum('tipo_documento', ['DNI', 'RUC', 'CE', 'PASAPORTE'])
                ->default('DNI');

            // Número del documento de identidad (único en el sistema)
            // Longitud de 20 caracteres para soportar formatos internacionales
            $table->string('numero_documento', 20)->unique();

            // Nombres de la persona (obligatorio)
            // Para personas jurídicas, puede contener el nombre comercial
            $table->string('nombres', 100);

            // Apellidos de la persona (opcional para personas jurídicas)
            // Nullable permite registrar empresas sin este campo
            $table->string('apellidos', 100)->nullable();

            // Razón social para personas jurídicas (empresas con RUC)
            // Se utiliza cuando tipo_documento es 'RUC'
            $table->string('razon_social', 200)->nullable();

            // Correo electrónico de contacto
            // Importante para comunicaciones y notificaciones del sistema
            $table->string('email', 150)->nullable();

            // Teléfono fijo de contacto
            // Formato flexible para soportar códigos de área internacionales
            $table->string('telefono', 20)->nullable();

            // Número de teléfono celular/móvil
            // Canal de comunicación prioritario para notificaciones
            $table->string('celular', 20)->nullable();

            // Dirección física completa (calle, número, referencias)
            $table->string('direccion', 250)->nullable();

            // Ciudad de residencia o ubicación principal
            $table->string('ciudad', 100)->nullable();

            // Provincia, departamento o estado
            $table->string('provincia', 100)->nullable();

            // País de residencia
            // Por defecto 'Bolivia', pero soporta internacionalización
            $table->string('pais', 100)->default('Bolivia');

            // Fecha de nacimiento de la persona
            // Útil para cálculos de edad, validaciones y análisis demográficos
            $table->date('fecha_nacimiento')->nullable();

            // Género o sexo de la persona
            // M: Masculino | F: Femenino | Otro: Identidades no binarias
            $table->enum('sexo', ['M', 'F', 'Otro'])->nullable();

            // Estado del registro: true = activo, false = inactivo
            // Permite desactivar sin eliminar para mantener integridad referencial
            $table->boolean('estado')->default(true);

            // Timestamps automáticos (created_at, updated_at)
            // Auditoría de creación y última modificación del registro
            $table->timestamps();

            // Índices para optimización de consultas frecuentes
            // Mejoran rendimiento en búsquedas, filtros y joins
            $table->index('tipo_documento');      // Filtros por tipo de documento
            $table->index('numero_documento');    // Búsquedas rápidas por documento (además del unique)
            $table->index('nombres');             // Búsquedas alfabéticas y autocompletado
            $table->index('estado');              // Filtros por registros activos/inactivos
        });
    }

    /**
     * Revierte la migración eliminando la tabla personas
     */
    public function down(): void
    {
        Schema::dropIfExists('personas');
    }
};
