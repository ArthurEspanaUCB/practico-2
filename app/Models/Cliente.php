<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

/**
 * Modelo Cliente
 *
 * Representa un cliente del sistema de ventas.
 * Extiende la información base de Persona con datos comerciales específicos
 * como gestión de crédito, descuentos, historial de compras y clasificación.
 *
 * @property int $id
 * @property int $persona_id
 * @property string $codigo
 * @property string $tipo_cliente
 * @property float $limite_credito
 * @property float $credito_usado
 * @property int $dias_credito
 * @property float $descuento_general
 * @property string|null $observaciones
 * @property \Illuminate\Support\Carbon $fecha_registro
 * @property \Illuminate\Support\Carbon|null $ultima_compra
 * @property float $total_compras
 * @property bool $estado
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read float $credito_disponible Crédito disponible calculado
 * @property-read bool $tiene_credito_disponible Si tiene crédito disponible
 * @property-read float $porcentaje_credito_usado Porcentaje de crédito utilizado
 * @property-read Persona $persona Relación con persona
 * @property-read \Illuminate\Database\Eloquent\Collection|Venta[] $ventas Ventas del cliente
 *
 * @method static Builder activos() Scope para filtrar clientes activos
 * @method static Builder vip() Scope para filtrar clientes VIP
 * @method static Builder porTipo(string $tipo) Scope para filtrar por tipo de cliente
 * @method static Builder conCredito() Scope para clientes con crédito disponible
 */
class Cliente extends Model
{
    use HasFactory;

    /**
     * Nombre de la tabla asociada al modelo
     *
     * @var string
     */
    protected $table = 'clientes';

    /**
     * Atributos asignables en masa
     *
     * Define los campos que pueden ser llenados mediante asignación masiva
     * para protección contra vulnerabilidades de asignación masiva
     *
     * @var array<string>
     */
    protected $fillable = [
        'persona_id',          // ID de la persona asociada
        'codigo',              // Código único del cliente (ej: CLI000001)
        'tipo_cliente',        // Tipo: Regular, VIP, Corporativo, Mayorista
        'limite_credito',      // Límite máximo de crédito autorizado
        'credito_usado',       // Crédito actualmente en uso
        'dias_credito',        // Días de plazo para pago
        'descuento_general',   // Porcentaje de descuento general
        'observaciones',       // Notas adicionales
        'fecha_registro',      // Fecha de registro del cliente
        'ultima_compra',       // Fecha de última compra
        'total_compras',       // Total histórico de compras
        'estado',               // Estado activo/inactivo
    ];

    /**
     * Conversión de tipos de atributos
     *
     * Transforma automáticamente los atributos a los tipos especificados
     * al recuperarlos o asignarlos
     *
     * @var array<string, string>
     */
    protected $casts = [
        'limite_credito' => 'decimal:2',       // Formato decimal con 2 decimales
        'credito_usado' => 'decimal:2',        // Formato decimal con 2 decimales
        'dias_credito' => 'integer',           // Número entero de días
        'descuento_general' => 'decimal:2',    // Porcentaje con 2 decimales
        'total_compras' => 'decimal:2',        // Monto total con 2 decimales
        'fecha_registro' => 'date',            // Convierte a Carbon date
        'ultima_compra' => 'date',             // Convierte a Carbon date
        'estado' => 'boolean',                 // Convierte a booleano
        'created_at' => 'datetime',            // Convierte a Carbon datetime
        'updated_at' => 'datetime',             // Convierte a Carbon datetime
    ];

    /**
     * Atributos computados agregados a las representaciones del modelo
     *
     * Estos accessors se incluyen automáticamente en JSON/array
     * NOTA: Puede afectar rendimiento en consultas masivas
     *
     * @var array<string>
     */
    protected $appends = [
        'credito_disponible',          // Crédito disponible calculado
        'tiene_credito_disponible',    // Booleano si tiene crédito
        'porcentaje_credito_usado',     // Porcentaje de uso del crédito
    ];

    /**
     * Constantes para tipos de cliente
     * Facilita el uso consistente y previene errores de escritura
     */
    public const TIPO_REGULAR = 'Regular';

    public const TIPO_VIP = 'VIP';

    public const TIPO_CORPORATIVO = 'Corporativo';

    public const TIPO_MAYORISTA = 'Mayorista';

    /**
     * Prefijo para generación de códigos de cliente
     */
    public const CODIGO_PREFIJO = 'CLI';

    /*
    |--------------------------------------------------------------------------
    | RELACIONES ELOQUENT
    |--------------------------------------------------------------------------
    */

    /**
     * Relación inversa con Persona
     *
     * Un cliente pertenece a una persona.
     * Permite acceder a los datos personales (nombre, documento, contacto, etc.)
     */
    public function persona(): BelongsTo
    {
        return $this->belongsTo(Persona::class, 'persona_id');
    }

    /**
     * Relación con Ventas
     *
     * Un cliente puede tener múltiples ventas.
     * Preparado para el módulo de gestión de ventas.
     *
     * @return HasMany
     */
    /*public function ventas(): HasMany
    {
        return $this->hasMany(Venta::class, 'cliente_id');
    }*/

    /*
    |--------------------------------------------------------------------------
    | QUERY SCOPES
    |--------------------------------------------------------------------------
    */

    /**
     * Scope para filtrar solo clientes activos
     *
     * Uso: Cliente::activos()->get()
     */
    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('estado', true);
    }

    /**
     * Scope para filtrar clientes VIP
     *
     * Uso: Cliente::vip()->get()
     */
    public function scopeVip(Builder $query): Builder
    {
        return $query->where('tipo_cliente', self::TIPO_VIP);
    }

    /**
     * Scope para filtrar por tipo de cliente
     *
     * Uso: Cliente::porTipo('Mayorista')->get()
     */
    public function scopePorTipo(Builder $query, string $tipo): Builder
    {
        return $query->where('tipo_cliente', $tipo);
    }

    /**
     * Scope para clientes con crédito disponible
     *
     * Filtra clientes que tienen límite de crédito y aún tienen saldo disponible
     * Uso: Cliente::conCredito()->get()
     */
    public function scopeConCredito(Builder $query): Builder
    {
        return $query->where('limite_credito', '>', 0)
            ->whereRaw('credito_usado < limite_credito');
    }

    /**
     * Scope para incluir datos de la persona relacionada
     *
     * Uso: Cliente::conPersona()->get()
     */
    public function scopeConPersona(Builder $query): Builder
    {
        return $query->with('persona');
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS (GETTERS)
    |--------------------------------------------------------------------------
    */

    /**
     * Accessor para calcular el crédito disponible
     *
     * Retorna el saldo de crédito que el cliente aún puede utilizar.
     * Fórmula: límite_credito - credito_usado
     */
    public function getCreditoDisponibleAttribute(): float
    {
        return max(0, $this->limite_credito - $this->credito_usado);
    }

    /**
     * Accessor para verificar si tiene crédito disponible
     *
     * Retorna true si el cliente tiene saldo de crédito disponible
     */
    public function getTieneCreditoDisponibleAttribute(): bool
    {
        return $this->credito_disponible > 0 && $this->dias_credito > 0;
    }

    /**
     * Accessor para calcular el porcentaje de crédito usado
     *
     * Retorna el porcentaje de uso del límite de crédito (0-100)
     * Útil para alertas y análisis de riesgo crediticio
     */
    public function getPorcentajeCreditoUsadoAttribute(): float
    {
        if ($this->limite_credito == 0) {
            return 0;
        }

        return round(($this->credito_usado / $this->limite_credito) * 100, 2);
    }

    /*
    |--------------------------------------------------------------------------
    | MÉTODOS ESTÁTICOS
    |--------------------------------------------------------------------------
    */

    /**
     * Genera un código único automático para el cliente
     *
     * Formato: CLI000001, CLI000002, etc.
     * Usa transacción para evitar códigos duplicados en concurrencia
     *
     * @return string Código generado
     */
    public static function generarCodigo(): string
    {
        return DB::transaction(function () {
            // Obtener el último cliente creado
            $ultimo = self::lockForUpdate()->orderBy('id', 'desc')->first();

            // Extraer número del código y sumar 1
            $numero = $ultimo ? (int) substr($ultimo->codigo, strlen(self::CODIGO_PREFIJO)) + 1 : 1;

            // Formatear código con padding de ceros
            return self::CODIGO_PREFIJO.str_pad($numero, 6, '0', STR_PAD_LEFT);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | MÉTODOS DE NEGOCIO - GESTIÓN DE CRÉDITO
    |--------------------------------------------------------------------------
    */

    /**
     * Verifica si el cliente puede realizar una compra a crédito
     *
     * Valida que:
     * - Tenga crédito disponible suficiente
     * - Tenga días de crédito configurados
     * - El cliente esté activo
     *
     * @param  float  $monto  Monto de la compra a verificar
     */
    public function puedeComprarACredito(float $monto): bool
    {
        return $this->estado
            && $this->credito_disponible >= $monto
            && $this->dias_credito > 0;
    }

    /**
     * Usa (consume) crédito del cliente
     *
     * Incrementa el crédito usado cuando se realiza una venta a crédito.
     * Valida que no exceda el límite de crédito antes de aplicar.
     *
     * @param  float  $monto  Monto a descontar del crédito disponible
     * @return bool True si se aplicó correctamente, false si excede límite
     *
     * @throws \Exception Si el monto excede el crédito disponible
     */
    public function usarCredito(float $monto): bool
    {
        // Validar que no exceda el límite
        if (($this->credito_usado + $monto) > $this->limite_credito) {
            throw new \Exception(
                "El monto ({$monto}) excede el crédito disponible ({$this->credito_disponible})"
            );
        }

        // Incrementar crédito usado
        $this->credito_usado += $monto;

        return $this->save();
    }

    /**
     * Libera (devuelve) crédito al cliente
     *
     * Decrementa el crédito usado cuando se abona o cancela una deuda.
     * Se asegura que el crédito usado nunca sea negativo.
     *
     * @param  float  $monto  Monto a devolver al crédito disponible
     * @return bool True si se aplicó correctamente
     */
    public function liberarCredito(float $monto): bool
    {
        // Decrementar crédito usado, asegurando que no sea negativo
        $this->credito_usado = max(0, $this->credito_usado - $monto);

        return $this->save();
    }

    /**
     * Actualiza la fecha de última compra
     *
     * Debe llamarse cada vez que se confirma una venta
     *
     * @param  \Carbon\Carbon|string|null  $fecha  Fecha de la compra (default: hoy)
     */
    public function actualizarUltimaCompra($fecha = null): bool
    {
        $this->ultima_compra = $fecha ?? now();

        return $this->save();
    }

    /**
     * Incrementa el total de compras del cliente
     *
     * Se ejecuta al confirmar una venta
     *
     * @param  float  $monto  Monto de la venta a sumar
     */
    public function incrementarTotalCompras(float $monto): bool
    {
        $this->total_compras += $monto;

        return $this->save();
    }

    /*
    |--------------------------------------------------------------------------
    | MÉTODOS AUXILIARES
    |--------------------------------------------------------------------------
    */

    /**
     * Verifica si el cliente es VIP
     */
    public function esVip(): bool
    {
        return $this->tipo_cliente === self::TIPO_VIP;
    }

    /**
     * Verifica si el cliente es mayorista
     */
    public function esMayorista(): bool
    {
        return $this->tipo_cliente === self::TIPO_MAYORISTA;
    }

    /**
     * Verifica si el cliente es corporativo
     */
    public function esCorporativo(): bool
    {
        return $this->tipo_cliente === self::TIPO_CORPORATIVO;
    }

    /**
     * Obtiene el nombre completo del cliente desde la persona relacionada
     */
    public function getNombreCompleto(): string
    {
        return $this->persona->nombre_completo ?? 'Sin nombre';
    }

    /**
     * Obtiene el número de documento del cliente
     */
    public function getNumeroDocumento(): string
    {
        return $this->persona->numero_documento ?? '';
    }
}
