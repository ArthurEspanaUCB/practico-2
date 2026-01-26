<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Migración para crear la tabla 'proveedores'
 *
 * Esta tabla almacena la información específica de los proveedores del sistema,
 * vinculada con la tabla 'personas' que contiene los datos personales básicos.
 * Incluye gestión de crédito otorgado por el proveedor, categorización por rubro,
 * datos bancarios, información de contacto comercial, historial de compras y calificación.
 *
 * Diferencia clave con clientes: El crédito fluye en dirección opuesta
 * - Clientes: Nosotros les otorgamos crédito
 * - Proveedores: Ellos nos otorgan crédito a nosotros
 *
 * @created 2026-01-06
 */
return new class extends Migration
{
    /**
     * Ejecuta la migración para crear la tabla proveedores
     */
    public function up(): void
    {
        Schema::create('proveedores', function (Blueprint $table) {
            // Clave primaria autoincremental
            $table->id();

            // Relación con tabla personas (datos personales del proveedor)
            // onDelete('restrict') previene eliminar personas con proveedores asociados
            // Esto protege la integridad referencial del historial de compras
            $table->foreignId('persona_id')
                ->constrained('personas')
                ->onDelete('restrict');

            // Código único identificador del proveedor para uso interno
            // Útil para búsquedas rápidas y referencias en órdenes de compra
            // Ejemplo de formato: PROV000001, PROV000002, etc.
            $table->string('codigo', 50)->unique();

            // Clasificación del proveedor según tipo de suministro
            // Producto: Provee productos físicos (materia prima, mercadería)
            // Servicio: Provee servicios (mantenimiento, consultoría, transporte)
            // Ambos: Provee productos y servicios combinados
            $table->enum('tipo_proveedor', ['Producto', 'Servicio', 'Ambos'])
                ->default('Producto');

            // Rubro o industria del proveedor
            // Permite categorizar y filtrar proveedores por área de especialización
            // Ejemplos: Tecnología, Alimentos, Construcción, Textil, Logística
            $table->string('rubro', 150)->nullable();

            // Límite máximo de crédito que el proveedor nos otorga
            // IMPORTANTE: Es el crédito que ELLOS nos dan a NOSOTROS (inverso a clientes)
            // 10 dígitos totales, 2 decimales (ej: 99,999,999.99)
            // Permite compras a crédito según términos comerciales acordados
            $table->decimal('limite_credito', 10, 2)->default(0);

            // Crédito actualmente utilizado de nuestras compras pendientes de pago
            // Debe ser <= limite_credito (validar en lógica de negocio)
            // Se incrementa con cada compra a crédito y disminuye al pagar
            $table->decimal('credito_usado', 10, 2)->default(0);

            // Días de plazo que el proveedor nos otorga para pagar facturas
            // 0 = pago inmediato/contado
            // 30, 60, 90 = plazos comerciales comunes
            // Afecta flujo de caja y planificación financiera
            $table->integer('dias_credito')->default(0);

            // Porcentaje de descuento general que el proveedor nos otorga
            // IMPORTANTE: Es el descuento que ELLOS nos dan a NOSOTROS
            // 5 dígitos totales, 2 decimales (ej: 999.99% máximo)
            // Puede variar por volumen, promociones o acuerdos comerciales
            $table->decimal('descuento_general', 5, 2)->default(0);

            // Número de cuenta bancaria del proveedor
            // Necesario para realizar transferencias y pagos electrónicos
            $table->string('cuenta_bancaria', 50)->nullable();

            // Nombre de la entidad bancaria del proveedor
            // Facilita procesos de pago y conciliación bancaria
            $table->string('banco', 100)->nullable();

            // Nombre completo de la persona de contacto comercial
            // Punto de contacto principal para pedidos y negociaciones
            // Puede ser vendedor asignado, ejecutivo de cuenta, etc.
            $table->string('nombre_contacto', 150)->nullable();

            // Cargo o posición del contacto dentro de la empresa proveedora
            // Ejemplos: Gerente Comercial, Ejecutivo de Ventas, Representante
            $table->string('cargo_contacto', 100)->nullable();

            // Teléfono directo del contacto comercial
            // Línea de comunicación rápida para consultas y urgencias
            $table->string('telefono_contacto', 20)->nullable();

            // Correo electrónico del contacto comercial
            // Canal principal para envío de órdenes de compra y comunicaciones
            $table->string('email_contacto', 150)->nullable();

            // Notas adicionales, comentarios o información relevante del proveedor
            // Puede incluir: términos especiales, horarios de atención, restricciones, etc.
            $table->text('observaciones')->nullable();

            // Fecha de registro del proveedor en el sistema
            // Usa CURRENT_DATE de la base de datos para garantizar precisión
            // Útil para análisis de antigüedad y rotación de proveedores
            $table->date('fecha_registro')->default(DB::raw('CURRENT_DATE'));

            // Fecha de la última compra realizada al proveedor
            // Útil para análisis de recencia y identificar proveedores inactivos
            // Permite detectar relaciones comerciales que requieren reactivación
            $table->date('ultima_compra')->nullable();

            // Monto total histórico de todas las compras realizadas al proveedor
            // Se actualiza con cada orden de compra confirmada
            // Métrica clave para análisis de proveedores estratégicos y negociaciones
            $table->decimal('total_compras', 10, 2)->default(0);

            // Calificación o rating del desempeño del proveedor
            // Basado en: calidad de productos, cumplimiento de plazos, servicio
            // 1 = Malo | 2 = Regular | 3 = Bueno | 4 = Excelente | 5 = Sobresaliente
            // Sistema numérico permite promedios y análisis estadísticos más flexibles
            $table->tinyInteger('calificacion')->default(3)
                ->comment('1=Malo, 2=Regular, 3=Bueno, 4=Excelente, 5=Sobresaliente');

            // Estado del proveedor: true = activo, false = inactivo/suspendido
            // Permite desactivar proveedores sin eliminar su historial
            // Útil para suspensión temporal por incumplimientos o fin de relación comercial
            $table->boolean('estado')->default(true);

            // Timestamps automáticos (created_at, updated_at)
            // Auditoría de creación y última modificación del registro
            $table->timestamps();

            // Soft deletes: permite "eliminar" sin borrar físicamente
            // Mantiene integridad referencial con órdenes de compra históricas
            // Permite restauración si se desactiva por error
            $table->softDeletes();

            // Campos de auditoría: rastreo de usuarios que modifican registros
            // created_by: Usuario que creó el proveedor
            // updated_by: Usuario que realizó la última modificación
            // deleted_by: Usuario que desactivó/eliminó el proveedor
            $table->unsignedBigInteger('created_by')->nullable()
                ->comment('ID del usuario que creó el registro');
            $table->unsignedBigInteger('updated_by')->nullable()
                ->comment('ID del usuario que realizó la última modificación');
            $table->unsignedBigInteger('deleted_by')->nullable()
                ->comment('ID del usuario que eliminó el registro');

            // Índices para optimización de consultas frecuentes
            // Mejoran rendimiento en búsquedas, filtros y reportes
            $table->index('codigo');              // Búsquedas por código de proveedor
            $table->index('tipo_proveedor');      // Filtros por tipo de suministro
            $table->index('rubro');               // Filtros por industria/rubro
            $table->index('estado');              // Filtros por estado activo/inactivo
            $table->index('fecha_registro');      // Reportes por período de registro
            $table->index('ultima_compra');       // Análisis de recencia de compras
            $table->index('calificacion');        // Filtros por calificación de desempeño
        });
    }

    /**
     * Revierte la migración eliminando la tabla proveedores
     */
    public function down(): void
    {
        Schema::dropIfExists('proveedores');
    }
};
