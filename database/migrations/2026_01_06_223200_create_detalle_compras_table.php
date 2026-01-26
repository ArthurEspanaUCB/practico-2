<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración para crear la tabla 'detalle_compras'
 *
 * Esta tabla almacena los productos incluidos en cada compra.
 * Es la tabla detalle del módulo de compras (relación maestro-detalle).
 *
 * Relaciones:
 * - compras (belongsTo): Compra a la que pertenece el detalle
 * - productos (belongsTo): Producto comprado
 *
 * @created 2026-01-06
 */
return new class extends Migration
{
    /**
     * Ejecuta la migración para crear la tabla detalle_compras
     */
    public function up(): void
    {
        Schema::create('detalle_compras', function (Blueprint $table) {
            // Clave primaria autoincremental
            $table->id();

            // Relación con tabla compras
            // onDelete('cascade') elimina detalles si se elimina la compra
            $table->foreignId('compra_id')
                ->constrained('compras')
                ->onDelete('cascade');

            // Relación con tabla productos
            // onDelete('restrict') previene eliminar productos con compras
            $table->foreignId('producto_id')
                ->constrained('productos')
                ->onDelete('restrict');

            // Cantidad de unidades compradas
            $table->integer('cantidad')->default(1);

            // Precio unitario del producto al momento de la compra
            // Se guarda para mantener historial aunque cambie el precio
            $table->decimal('precio_unitario', 10, 2)->default(0);

            // Porcentaje de descuento específico para este ítem
            // Puede ser diferente al descuento general de la compra
            $table->decimal('porcentaje_descuento', 5, 2)->default(0);

            // Monto de descuento del ítem
            $table->decimal('descuento', 10, 2)->default(0);

            // Subtotal del ítem: (cantidad * precio_unitario) - descuento
            $table->decimal('subtotal', 12, 2)->default(0);

            // Timestamps automáticos
            $table->timestamps();

            // Índices para optimización
            $table->index('compra_id');
            $table->index('producto_id');

            // Índice compuesto para evitar duplicados del mismo producto
            // en la misma compra (opcional, depende de regla de negocio)
            // $table->unique(['compra_id', 'producto_id']);
        });
    }

    /**
     * Revierte la migración eliminando la tabla detalle_compras
     */
    public function down(): void
    {
        Schema::dropIfExists('detalle_compras');
    }
};
