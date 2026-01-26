<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo DetalleCompra
 *
 * Representa un ítem (producto) dentro de una orden de compra.
 * Es el modelo detalle del módulo de compras (relación maestro-detalle).
 *
 * @property int $id
 * @property int $compra_id
 * @property int $producto_id
 * @property int $cantidad
 * @property float $precio_unitario
 * @property float $porcentaje_descuento
 * @property float $descuento
 * @property float $subtotal
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Compra $compra Relación con compra
 * @property-read Producto $producto Relación con producto
 * @property-read float $precio_base Precio base sin descuento
 */
class DetalleCompra extends Model
{
    use HasFactory;

    /**
     * Nombre de la tabla asociada al modelo
     *
     * @var string
     */
    protected $table = 'detalle_compras';

    /**
     * Atributos asignables en masa
     *
     * @var array<string>
     */
    protected $fillable = [
        'compra_id',
        'producto_id',
        'cantidad',
        'precio_unitario',
        'porcentaje_descuento',
        'descuento',
        'subtotal',
    ];

    /**
     * Conversión de tipos de atributos
     *
     * @var array<string, string>
     */
    protected $casts = [
        'cantidad' => 'integer',
        'precio_unitario' => 'decimal:2',
        'porcentaje_descuento' => 'decimal:2',
        'descuento' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Atributos computados agregados a JSON/array
     *
     * @var array<string>
     */
    protected $appends = [
        'precio_base',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELACIONES ELOQUENT
    |--------------------------------------------------------------------------
    */

    /**
     * Relación con Compra
     */
    public function compra(): BelongsTo
    {
        return $this->belongsTo(Compra::class, 'compra_id');
    }

    /**
     * Relación con Producto
     */
    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS (GETTERS)
    |--------------------------------------------------------------------------
    */

    /**
     * Precio base (cantidad * precio_unitario) sin descuento
     */
    public function getPrecioBaseAttribute(): float
    {
        return round($this->cantidad * $this->precio_unitario, 2);
    }

    /*
    |--------------------------------------------------------------------------
    | MÉTODOS DE NEGOCIO
    |--------------------------------------------------------------------------
    */

    /**
     * Calcula y actualiza el subtotal del detalle
     */
    public function calcularSubtotal(): bool
    {
        // Calcular precio base
        $precioBase = $this->cantidad * $this->precio_unitario;

        // Calcular descuento
        $this->descuento = round($precioBase * ($this->porcentaje_descuento / 100), 2);

        // Calcular subtotal
        $this->subtotal = round($precioBase - $this->descuento, 2);

        return $this->save();
    }

    /**
     * Evento al crear/actualizar: recalcula subtotal automáticamente
     */
    protected static function booted(): void
    {
        // Antes de guardar, calcular subtotal
        static::saving(function (DetalleCompra $detalle) {
            $precioBase = $detalle->cantidad * $detalle->precio_unitario;
            $detalle->descuento = round($precioBase * ($detalle->porcentaje_descuento / 100), 2);
            $detalle->subtotal = round($precioBase - $detalle->descuento, 2);
        });

        // Después de guardar, recalcular totales de la compra
        static::saved(function (DetalleCompra $detalle) {
            if ($detalle->compra) {
                $detalle->compra->calcularTotales();
            }
        });

        // Después de eliminar, recalcular totales de la compra
        static::deleted(function (DetalleCompra $detalle) {
            if ($detalle->compra) {
                $detalle->compra->calcularTotales();
            }
        });
    }
}
