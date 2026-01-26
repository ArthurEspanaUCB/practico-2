<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo DetalleVenta
 *
 * Representa un ítem (producto) dentro de una venta.
 * Es el modelo detalle del módulo de ventas (relación maestro-detalle).
 */
class DetalleVenta extends Model
{
    use HasFactory;

    protected $table = 'detalle_ventas';

    protected $fillable = [
        'venta_id',
        'producto_id',
        'cantidad',
        'precio_unitario',
        'porcentaje_descuento',
        'descuento',
        'subtotal',
    ];

    protected $casts = [
        'cantidad' => 'integer',
        'precio_unitario' => 'decimal:2',
        'porcentaje_descuento' => 'decimal:2',
        'descuento' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = [
        'precio_base',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELACIONES ELOQUENT
    |--------------------------------------------------------------------------
    */

    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class, 'venta_id');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS (GETTERS)
    |--------------------------------------------------------------------------
    */

    public function getPrecioBaseAttribute(): float
    {
        return round($this->cantidad * $this->precio_unitario, 2);
    }

    /*
    |--------------------------------------------------------------------------
    | EVENTOS DEL MODELO
    |--------------------------------------------------------------------------
    */

    protected static function booted(): void
    {
        // Antes de guardar, calcular subtotal
        static::saving(function (DetalleVenta $detalle) {
            $precioBase = $detalle->cantidad * $detalle->precio_unitario;
            $detalle->descuento = round($precioBase * ($detalle->porcentaje_descuento / 100), 2);
            $detalle->subtotal = round($precioBase - $detalle->descuento, 2);
        });

        // Después de guardar, recalcular totales de la venta
        static::saved(function (DetalleVenta $detalle) {
            if ($detalle->venta) {
                $detalle->venta->calcularTotales();
            }
        });

        // Después de eliminar, recalcular totales de la venta
        static::deleted(function (DetalleVenta $detalle) {
            if ($detalle->venta) {
                $detalle->venta->calcularTotales();
            }
        });
    }
}
