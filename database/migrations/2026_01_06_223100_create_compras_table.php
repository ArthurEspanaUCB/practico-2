<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Migración para crear la tabla 'compras'
 *
 * Esta tabla almacena las órdenes de compra realizadas a proveedores.
 * Es la tabla maestra del módulo de compras, relacionada con 'detalle_compras'.
 *
 * Relaciones:
 * - proveedores (belongsTo): Proveedor al que se realiza la compra
 * - detalle_compras (hasMany): Líneas de detalle con los productos
 *
 * @created 2026-01-06
 */
return new class extends Migration
{
    /**
     * Ejecuta la migración para crear la tabla compras
     */
    public function up(): void
    {
        Schema::create('compras', function (Blueprint $table) {
            // Clave primaria autoincremental
            $table->id();

            // Relación con tabla proveedores
            // onDelete('restrict') previene eliminar proveedores con compras
            $table->foreignId('proveedor_id')
                ->constrained('proveedores')
                ->onDelete('restrict');

            // Código único identificador de la compra
            // Formato: COMP000001, COMP000002, etc.
            $table->string('codigo', 50)->unique();

            // Tipo de compra según forma de pago
            // Contado: Pago inmediato
            // Credito: Pago a plazos según días de crédito del proveedor
            $table->enum('tipo_compra', ['Contado', 'Credito'])
                ->default('Contado');

            // Tipo de comprobante recibido del proveedor
            $table->enum('tipo_comprobante', ['Factura', 'Nota', 'Recibo', 'Otro'])
                ->default('Factura');

            // Número del comprobante del proveedor
            // (número de factura, nota de venta, etc.)
            $table->string('numero_comprobante', 100)->nullable();

            // Fecha en que se realiza la compra
            $table->date('fecha_compra')->default(DB::raw('CURRENT_DATE'));

            // Fecha de vencimiento del pago (para compras a crédito)
            // Calculada: fecha_compra + dias_credito del proveedor
            $table->date('fecha_vencimiento')->nullable();

            // Subtotal antes de impuestos y descuentos
            // Suma de (cantidad * precio_unitario) de todos los detalles
            $table->decimal('subtotal', 12, 2)->default(0);

            // Porcentaje de impuesto aplicado (ej: 13% IVA)
            $table->decimal('porcentaje_impuesto', 5, 2)->default(0);

            // Monto de impuesto calculado
            $table->decimal('impuesto', 12, 2)->default(0);

            // Porcentaje de descuento general sobre la compra
            $table->decimal('porcentaje_descuento', 5, 2)->default(0);

            // Monto de descuento calculado
            $table->decimal('descuento', 12, 2)->default(0);

            // Total de la compra (subtotal + impuesto - descuento)
            $table->decimal('total', 12, 2)->default(0);

            // Estado de la compra
            // Pendiente: Recién creada, puede modificarse
            // Completada: Confirmada, actualiza stock y crédito
            // Anulada: Cancelada, no se considera en reportes
            $table->enum('estado', ['Pendiente', 'Completada', 'Anulada'])
                ->default('Pendiente');

            // Notas adicionales sobre la compra
            $table->text('observaciones')->nullable();

            // Timestamps automáticos
            $table->timestamps();

            // Campos de auditoría
            $table->unsignedBigInteger('created_by')->nullable()
                ->comment('ID del usuario que creó la compra');
            $table->unsignedBigInteger('updated_by')->nullable()
                ->comment('ID del usuario que modificó la compra');

            // Índices para optimización
            $table->index('codigo');
            $table->index('proveedor_id');
            $table->index('tipo_compra');
            $table->index('estado');
            $table->index('fecha_compra');
            $table->index('fecha_vencimiento');
        });
    }

    /**
     * Revierte la migración eliminando la tabla compras
     */
    public function down(): void
    {
        Schema::dropIfExists('compras');
    }
};
