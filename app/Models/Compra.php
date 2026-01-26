<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

/**
 * Modelo Compra
 *
 * Representa una orden de compra realizada a un proveedor.
 * Es el modelo maestro del módulo de compras, relacionado con DetalleCompra.
 *
 * @property int $id
 * @property int $proveedor_id
 * @property string $codigo
 * @property string $tipo_compra
 * @property string $tipo_comprobante
 * @property string|null $numero_comprobante
 * @property \Illuminate\Support\Carbon $fecha_compra
 * @property \Illuminate\Support\Carbon|null $fecha_vencimiento
 * @property float $subtotal
 * @property float $porcentaje_impuesto
 * @property float $impuesto
 * @property float $porcentaje_descuento
 * @property float $descuento
 * @property float $total
 * @property string $estado
 * @property string|null $observaciones
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Proveedor $proveedor Relación con proveedor
 * @property-read \Illuminate\Database\Eloquent\Collection|DetalleCompra[] $detalles Detalles de la compra
 * @property-read int $cantidad_items Cantidad de ítems en la compra
 * @property-read bool $es_credito Si la compra es a crédito
 * @property-read bool $puede_editarse Si la compra puede modificarse
 *
 * @method static Builder pendientes() Scope para compras pendientes
 * @method static Builder completadas() Scope para compras completadas
 * @method static Builder anuladas() Scope para compras anuladas
 * @method static Builder porProveedor(int $proveedorId) Scope para filtrar por proveedor
 * @method static Builder entreFechas(string $desde, string $hasta) Scope para filtrar por rango de fechas
 */
class Compra extends Model
{
    use HasFactory;

    /**
     * Nombre de la tabla asociada al modelo
     *
     * @var string
     */
    protected $table = 'compras';

    /**
     * Atributos asignables en masa
     *
     * @var array<string>
     */
    protected $fillable = [
        'proveedor_id',
        'codigo',
        'tipo_compra',
        'tipo_comprobante',
        'numero_comprobante',
        'fecha_compra',
        'fecha_vencimiento',
        'subtotal',
        'porcentaje_impuesto',
        'impuesto',
        'porcentaje_descuento',
        'descuento',
        'total',
        'estado',
        'observaciones',
        'created_by',
        'updated_by',
    ];

    /**
     * Conversión de tipos de atributos
     *
     * @var array<string, string>
     */
    protected $casts = [
        'fecha_compra' => 'date',
        'fecha_vencimiento' => 'date',
        'subtotal' => 'decimal:2',
        'porcentaje_impuesto' => 'decimal:2',
        'impuesto' => 'decimal:2',
        'porcentaje_descuento' => 'decimal:2',
        'descuento' => 'decimal:2',
        'total' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'created_by' => 'integer',
        'updated_by' => 'integer',
    ];

    /**
     * Atributos computados agregados a JSON/array
     *
     * @var array<string>
     */
    protected $appends = [
        'cantidad_items',
        'es_credito',
        'puede_editarse',
    ];

    /**
     * Constantes para tipos de compra
     */
    public const TIPO_CONTADO = 'Contado';

    public const TIPO_CREDITO = 'Credito';

    /**
     * Constantes para tipos de comprobante
     */
    public const COMPROBANTE_FACTURA = 'Factura';

    public const COMPROBANTE_NOTA = 'Nota';

    public const COMPROBANTE_RECIBO = 'Recibo';

    public const COMPROBANTE_OTRO = 'Otro';

    /**
     * Constantes para estados
     */
    public const ESTADO_PENDIENTE = 'Pendiente';

    public const ESTADO_COMPLETADA = 'Completada';

    public const ESTADO_ANULADA = 'Anulada';

    /**
     * Prefijo para generación de códigos
     */
    public const CODIGO_PREFIJO = 'COMP';

    /*
    |--------------------------------------------------------------------------
    | RELACIONES ELOQUENT
    |--------------------------------------------------------------------------
    */

    /**
     * Relación con Proveedor
     */
    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class, 'proveedor_id');
    }

    /**
     * Relación con DetalleCompra
     */
    public function detalles(): HasMany
    {
        return $this->hasMany(DetalleCompra::class, 'compra_id');
    }

    /*
    |--------------------------------------------------------------------------
    | QUERY SCOPES
    |--------------------------------------------------------------------------
    */

    /**
     * Scope para compras pendientes
     */
    public function scopePendientes(Builder $query): Builder
    {
        return $query->where('estado', self::ESTADO_PENDIENTE);
    }

    /**
     * Scope para compras completadas
     */
    public function scopeCompletadas(Builder $query): Builder
    {
        return $query->where('estado', self::ESTADO_COMPLETADA);
    }

    /**
     * Scope para compras anuladas
     */
    public function scopeAnuladas(Builder $query): Builder
    {
        return $query->where('estado', self::ESTADO_ANULADA);
    }

    /**
     * Scope para filtrar por proveedor
     */
    public function scopePorProveedor(Builder $query, int $proveedorId): Builder
    {
        return $query->where('proveedor_id', $proveedorId);
    }

    /**
     * Scope para filtrar por rango de fechas
     */
    public function scopeEntreFechas(Builder $query, string $desde, string $hasta): Builder
    {
        return $query->whereBetween('fecha_compra', [$desde, $hasta]);
    }

    /**
     * Scope para incluir relaciones comunes
     */
    public function scopeConRelaciones(Builder $query): Builder
    {
        return $query->with(['proveedor.persona', 'detalles.producto']);
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS (GETTERS)
    |--------------------------------------------------------------------------
    */

    /**
     * Cantidad de ítems en la compra
     */
    public function getCantidadItemsAttribute(): int
    {
        return $this->detalles->count();
    }

    /**
     * Verifica si la compra es a crédito
     */
    public function getEsCreditoAttribute(): bool
    {
        return $this->tipo_compra === self::TIPO_CREDITO;
    }

    /**
     * Verifica si la compra puede editarse
     * Solo las compras pendientes pueden modificarse
     */
    public function getPuedeEditarseAttribute(): bool
    {
        return $this->estado === self::ESTADO_PENDIENTE;
    }

    /*
    |--------------------------------------------------------------------------
    | MÉTODOS ESTÁTICOS
    |--------------------------------------------------------------------------
    */

    /**
     * Genera un código único automático para la compra
     * Formato: COMP000001, COMP000002, etc.
     */
    public static function generarCodigo(): string
    {
        return DB::transaction(function () {
            $ultimo = self::lockForUpdate()->orderBy('id', 'desc')->first();
            $numero = $ultimo ? (int) substr($ultimo->codigo, strlen(self::CODIGO_PREFIJO)) + 1 : 1;

            return self::CODIGO_PREFIJO.str_pad($numero, 6, '0', STR_PAD_LEFT);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | MÉTODOS DE NEGOCIO
    |--------------------------------------------------------------------------
    */

    /**
     * Calcula y actualiza los totales de la compra
     * Basado en los detalles actuales
     */
    public function calcularTotales(): bool
    {
        // Recargar detalles
        $this->load('detalles');

        // Calcular subtotal sumando todos los detalles
        $subtotal = $this->detalles->sum('subtotal');

        // Calcular descuento general
        $descuento = $subtotal * ($this->porcentaje_descuento / 100);

        // Calcular base imponible
        $baseImponible = $subtotal - $descuento;

        // Calcular impuesto
        $impuesto = $baseImponible * ($this->porcentaje_impuesto / 100);

        // Calcular total
        $total = $baseImponible + $impuesto;

        // Actualizar valores
        $this->subtotal = round($subtotal, 2);
        $this->descuento = round($descuento, 2);
        $this->impuesto = round($impuesto, 2);
        $this->total = round($total, 2);

        return $this->save();
    }

    /**
     * Completa la compra
     * - Cambia estado a Completada
     * - Actualiza stock de productos
     * - Actualiza crédito del proveedor (si aplica)
     * - Actualiza última compra y total de compras del proveedor
     *
     * @throws \Exception Si la compra no puede completarse
     */
    public function completar(): bool
    {
        if ($this->estado !== self::ESTADO_PENDIENTE) {
            throw new \Exception('Solo las compras pendientes pueden completarse');
        }

        return DB::transaction(function () {
            // Actualizar stock de productos
            foreach ($this->detalles as $detalle) {
                $producto = $detalle->producto;
                $producto->stock += $detalle->cantidad;
                $producto->save();
            }

            // Si es a crédito, usar crédito del proveedor
            if ($this->es_credito) {
                $this->proveedor->usarCredito($this->total);
            }

            // Actualizar datos del proveedor
            $this->proveedor->actualizarUltimaCompra($this->fecha_compra);
            $this->proveedor->incrementarTotalCompras($this->total);

            // Cambiar estado
            $this->estado = self::ESTADO_COMPLETADA;

            return $this->save();
        });
    }

    /**
     * Anula la compra
     * - Cambia estado a Anulada
     * - Si estaba completada, revierte stock y crédito
     *
     * @throws \Exception Si la compra no puede anularse
     */
    public function anular(): bool
    {
        if ($this->estado === self::ESTADO_ANULADA) {
            throw new \Exception('La compra ya está anulada');
        }

        return DB::transaction(function () {
            // Si estaba completada, revertir cambios
            if ($this->estado === self::ESTADO_COMPLETADA) {
                // Revertir stock de productos
                foreach ($this->detalles as $detalle) {
                    $producto = $detalle->producto;
                    $producto->stock -= $detalle->cantidad;
                    $producto->save();
                }

                // Si era a crédito, liberar crédito del proveedor
                if ($this->es_credito) {
                    $this->proveedor->liberarCredito($this->total);
                }
            }

            // Cambiar estado
            $this->estado = self::ESTADO_ANULADA;

            return $this->save();
        });
    }

    /**
     * Verifica si la compra está vencida (para créditos)
     */
    public function estaVencida(): bool
    {
        if (! $this->es_credito || ! $this->fecha_vencimiento) {
            return false;
        }

        return $this->fecha_vencimiento->isPast();
    }

    /**
     * Calcula los días restantes para el vencimiento
     */
    public function diasParaVencimiento(): ?int
    {
        if (! $this->es_credito || ! $this->fecha_vencimiento) {
            return null;
        }

        return now()->diffInDays($this->fecha_vencimiento, false);
    }
}
