<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración para crear la tabla 'detalle_ventas'
 *
 * Esta tabla almacena los productos incluidos en cada venta.
 * Es la tabla detalle del módulo de ventas (relación maestro-detalle).
 *
 * Relaciones:
 * - ventas (belongsTo): Venta a la que pertenece el detalle
 * - productos (belongsTo): Producto vendido
 *
 * @created 2026-01-06
 */
return new class extends Migration
{
    /**
     * Ejecuta la migración para crear la tabla detalle_ventas
     */
    public function up(): void
    {
        Schema::create('detalle_ventas', function (Blueprint $table) {
            // Clave primaria autoincremental
            $table->id();

            // Relación con tabla ventas
            // onDelete('cascade') elimina detalles si se elimina la venta
            $table->foreignId('venta_id')
                ->constrained('ventas')
                ->onDelete('cascade');

            // Relación con tabla productos
            // onDelete('restrict') previene eliminar productos con ventas
            $table->foreignId('producto_id')
                ->constrained('productos')
                ->onDelete('restrict');

            // Cantidad de unidades vendidas
            $table->integer('cantidad')->default(1);

            // Precio unitario del producto al momento de la venta
            // Se guarda precio_venta para mantener historial
            $table->decimal('precio_unitario', 10, 2)->default(0);

            // Porcentaje de descuento específico para este ítem
            $table->decimal('porcentaje_descuento', 5, 2)->default(0);

            // Monto de descuento del ítem
            $table->decimal('descuento', 10, 2)->default(0);

            // Subtotal del ítem: (cantidad * precio_unitario) - descuento
            $table->decimal('subtotal', 12, 2)->default(0);

            // Timestamps automáticos
            $table->timestamps();

            // Índices para optimización
            $table->index('venta_id');
            $table->index('producto_id');
        });
    }

    /**
     * Revierte la migración eliminando la tabla detalle_ventas
     */
    public function down(): void
    {
        Schema::dropIfExists('detalle_ventas');
    }
};
