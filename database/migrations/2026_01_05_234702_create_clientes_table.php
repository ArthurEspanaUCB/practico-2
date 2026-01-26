<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Migración para crear la tabla 'clientes'
 *
 * Esta tabla almacena la información específica de los clientes del sistema,
 * vinculada con la tabla 'personas' que contiene los datos personales básicos.
 * Incluye gestión de crédito, tipos de cliente, historial de compras y descuentos.
 *
 * @created 2026-01-05
 */
return new class extends Migration
{
    /**
     * Ejecuta la migración para crear la tabla clientes
     */
    public function up(): void
    {
        Schema::create('clientes', function (Blueprint $table) {
            // Clave primaria autoincremental
            $table->id();

            // Relación con tabla personas (datos personales del cliente)
            // onDelete('restrict') previene eliminar personas con clientes asociados
            $table->foreignId('persona_id')
                ->constrained('personas')
                ->onDelete('restrict');

            // Código único identificador del cliente para uso interno
            // Útil para búsquedas rápidas y referencias en documentos
            $table->string('codigo', 50)->unique();

            // Clasificación del cliente para estrategias comerciales diferenciadas
            // Regular: Cliente estándar | VIP: Cliente preferencial
            // Corporativo: Empresas | Mayorista: Compras al por mayor
            $table->enum('tipo_cliente', ['Regular', 'VIP', 'Corporativo', 'Mayorista'])
                ->default('Regular');

            // Límite máximo de crédito autorizado para el cliente
            // 10 dígitos totales, 2 decimales (ej: 99,999,999.99)
            $table->decimal('limite_credito', 10, 2)->default(0);

            // Crédito actualmente utilizado por el cliente
            // Debe ser <= limite_credito (validar en lógica de negocio)
            $table->decimal('credito_usado', 10, 2)->default(0);

            // Días de plazo otorgados para pago de facturas a crédito
            // 0 = pago inmediato, >0 = días de plazo
            $table->integer('dias_credito')->default(0);

            // Porcentaje de descuento general aplicable al cliente
            // 5 dígitos totales, 2 decimales (ej: 999.99% máximo)
            $table->decimal('descuento_general', 5, 2)->default(0);

            // Notas adicionales, comentarios o información relevante del cliente
            $table->text('observaciones')->nullable();

            // Fecha de registro del cliente en el sistema
            // Usa CURRENT_DATE de la base de datos para garantizar precisión
            $table->date('fecha_registro')->default(DB::raw('CURRENT_DATE'));

            // Fecha de la última compra realizada por el cliente
            // Útil para análisis de recencia y segmentación RFM
            $table->date('ultima_compra')->nullable();

            // Monto total histórico de todas las compras del cliente
            // Se actualiza con cada venta confirmada
            $table->decimal('total_compras', 10, 2)->default(0);

            // Estado del cliente: true = activo, false = inactivo/suspendido
            // Permite desactivar clientes sin eliminar su historial
            $table->boolean('estado')->default(true);

            // Timestamps automáticos (created_at, updated_at)
            $table->timestamps();

            // Índices para optimización de consultas frecuentes
            // Mejoran rendimiento en búsquedas y filtros
            $table->index('codigo');         // Búsquedas por código de cliente
            $table->index('tipo_cliente');   // Filtros por tipo de cliente
            $table->index('estado');         // Filtros por estado activo/inactivo
        });
    }

    /**
     * Revierte la migración eliminando la tabla clientes
     */
    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};
