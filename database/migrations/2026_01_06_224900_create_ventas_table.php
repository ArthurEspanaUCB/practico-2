<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Migración para crear la tabla 'ventas'
 *
 * Esta tabla almacena las ventas realizadas a clientes.
 * Es la tabla maestra del módulo de ventas, relacionada con 'detalle_ventas'.
 *
 * Relaciones:
 * - clientes (belongsTo): Cliente al que se realiza la venta
 * - detalle_ventas (hasMany): Líneas de detalle con los productos
 *
 * @created 2026-01-06
 */
return new class extends Migration
{
    /**
     * Ejecuta la migración para crear la tabla ventas
     */
    public function up(): void
    {
        Schema::create('ventas', function (Blueprint $table) {
            // Clave primaria autoincremental
            $table->id();

            // Relación con tabla clientes
            // onDelete('restrict') previene eliminar clientes con ventas
            $table->foreignId('cliente_id')
                ->constrained('clientes')
                ->onDelete('restrict');

            // Código único identificador de la venta
            // Formato: VENT000001, VENT000002, etc.
            $table->string('codigo', 50)->unique();

            // Tipo de venta según forma de pago
            // Contado: Pago inmediato
            // Credito: Pago a plazos según días de crédito del cliente
            $table->enum('tipo_venta', ['Contado', 'Credito'])
                ->default('Contado');

            // Tipo de comprobante emitido
            $table->enum('tipo_comprobante', ['Factura', 'Boleta', 'Nota', 'Otro'])
                ->default('Factura');

            // Número del comprobante emitido
            $table->string('numero_comprobante', 100)->nullable();

            // Fecha en que se realiza la venta
            $table->date('fecha_venta')->default(DB::raw('CURRENT_DATE'));

            // Fecha de vencimiento del pago (para ventas a crédito)
            $table->date('fecha_vencimiento')->nullable();

            // Subtotal antes de impuestos y descuentos
            $table->decimal('subtotal', 12, 2)->default(0);

            // Porcentaje de impuesto aplicado (ej: 13% IVA)
            $table->decimal('porcentaje_impuesto', 5, 2)->default(0);

            // Monto de impuesto calculado
            $table->decimal('impuesto', 12, 2)->default(0);

            // Porcentaje de descuento general sobre la venta
            $table->decimal('porcentaje_descuento', 5, 2)->default(0);

            // Monto de descuento calculado
            $table->decimal('descuento', 12, 2)->default(0);

            // Total de la venta (subtotal + impuesto - descuento)
            $table->decimal('total', 12, 2)->default(0);

            // Estado de la venta
            // Pendiente: Recién creada, puede modificarse
            // Completada: Confirmada, actualiza stock y crédito
            // Anulada: Cancelada, no se considera en reportes
            $table->enum('estado', ['Pendiente', 'Completada', 'Anulada'])
                ->default('Pendiente');

            // Notas adicionales sobre la venta
            $table->text('observaciones')->nullable();

            // Timestamps automáticos
            $table->timestamps();

            // Campos de auditoría
            $table->unsignedBigInteger('created_by')->nullable()
                ->comment('ID del usuario que creó la venta');
            $table->unsignedBigInteger('updated_by')->nullable()
                ->comment('ID del usuario que modificó la venta');

            // Índices para optimización
            $table->index('codigo');
            $table->index('cliente_id');
            $table->index('tipo_venta');
            $table->index('estado');
            $table->index('fecha_venta');
            $table->index('fecha_vencimiento');
        });
    }

    /**
     * Revierte la migración eliminando la tabla ventas
     */
    public function down(): void
    {
        Schema::dropIfExists('ventas');
    }
};
