<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

/**
 * Modelo Proveedor
 *
 * Representa un proveedor del sistema de compras.
 * Extiende la información base de Persona con datos comerciales específicos
 * como gestión de crédito otorgado, calificación de desempeño, categorización
 * por rubro, datos bancarios e historial de compras.
 *
 * DIFERENCIA CLAVE CON CLIENTES:
 * - Clientes: Nosotros les otorgamos crédito a ellos
 * - Proveedores: Ellos nos otorgan crédito a nosotros
 *
 * @property int $id
 * @property int $persona_id
 * @property string $codigo
 * @property string $tipo_proveedor
 * @property string|null $rubro
 * @property float $limite_credito Crédito que el proveedor nos otorga
 * @property float $credito_usado Crédito que tenemos usado con el proveedor
 * @property int $dias_credito Plazo que el proveedor nos da para pagar
 * @property float $descuento_general Descuento que el proveedor nos otorga
 * @property string|null $cuenta_bancaria
 * @property string|null $banco
 * @property string|null $nombre_contacto
 * @property string|null $cargo_contacto
 * @property string|null $telefono_contacto
 * @property string|null $email_contacto
 * @property string|null $observaciones
 * @property \Illuminate\Support\Carbon $fecha_registro
 * @property \Illuminate\Support\Carbon|null $ultima_compra
 * @property float $total_compras
 * @property int $calificacion Rating del proveedor (1-5)
 * @property bool $estado
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property int|null $deleted_by
 * @property-read float $credito_disponible Crédito disponible que nos otorga el proveedor
 * @property-read bool $tiene_credito_disponible Si tenemos crédito disponible con este proveedor
 * @property-read float $porcentaje_credito_usado Porcentaje del crédito que hemos utilizado
 * @property-read string $calificacion_texto Texto de la calificación (Excelente, Bueno, etc.)
 * @property-read Persona $persona Relación con persona
 *
 * @method static Builder activos() Scope para filtrar proveedores activos
 * @method static Builder porTipo(string $tipo) Scope para filtrar por tipo de proveedor
 * @method static Builder porCalificacion(int $calificacion) Scope para filtrar por calificación
 * @method static Builder porRubro(string $rubro) Scope para filtrar por rubro
 * @method static Builder conCredito() Scope para proveedores con crédito disponible
 * @method static Builder mejorCalificados() Scope para proveedores con calificación >= 4
 */
class Proveedor extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Nombre de la tabla asociada al modelo
     *
     * @var string
     */
    protected $table = 'proveedores';

    /**
     * Atributos asignables en masa
     *
     * Define los campos que pueden ser llenados mediante asignación masiva
     * para protección contra vulnerabilidades de asignación masiva.
     * No incluye campos de auditoría (created_by, updated_by, deleted_by)
     * ya que se manejan mediante observadores o middleware.
     *
     * @var array<string>
     */
    protected $fillable = [
        'persona_id',          // ID de la persona asociada
        'codigo',              // Código único del proveedor (ej: PROV000001)
        'tipo_proveedor',      // Tipo: Producto, Servicio, Ambos
        'rubro',               // Industria/rubro (Tecnología, Alimentos, etc.)
        'limite_credito',      // Límite de crédito que NOS otorgan
        'credito_usado',       // Crédito que actualmente tenemos usado
        'dias_credito',        // Días de plazo que nos dan para pagar
        'descuento_general',   // Descuento que nos otorgan
        'cuenta_bancaria',     // Número de cuenta del proveedor
        'banco',               // Nombre del banco del proveedor
        'nombre_contacto',     // Nombre del contacto comercial
        'cargo_contacto',      // Cargo del contacto comercial
        'telefono_contacto',   // Teléfono del contacto
        'email_contacto',      // Email del contacto
        'observaciones',       // Notas adicionales
        'fecha_registro',      // Fecha de registro del proveedor
        'ultima_compra',       // Fecha de última compra al proveedor
        'total_compras',       // Total histórico de compras
        'calificacion',        // Calificación del proveedor (1-5)
        'estado',               // Estado activo/inactivo
    ];

    /**
     * Conversión de tipos de atributos
     *
     * Transforma automáticamente los atributos a los tipos especificados
     * al recuperarlos o asignarlos. Mejora la consistencia de datos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'limite_credito' => 'decimal:2',       // Formato decimal con 2 decimales
        'credito_usado' => 'decimal:2',        // Formato decimal con 2 decimales
        'dias_credito' => 'integer',           // Número entero de días
        'descuento_general' => 'decimal:2',    // Porcentaje con 2 decimales
        'total_compras' => 'decimal:2',        // Monto total con 2 decimales
        'calificacion' => 'integer',           // Calificación numérica 1-5
        'fecha_registro' => 'date',            // Convierte a Carbon date
        'ultima_compra' => 'date',             // Convierte a Carbon date
        'estado' => 'boolean',                 // Convierte a booleano
        'created_at' => 'datetime',            // Convierte a Carbon datetime
        'updated_at' => 'datetime',            // Convierte a Carbon datetime
        'deleted_at' => 'datetime',            // Soft delete timestamp
        'created_by' => 'integer',             // ID del usuario creador
        'updated_by' => 'integer',             // ID del usuario modificador
        'deleted_by' => 'integer',              // ID del usuario que eliminó
    ];

    /**
     * Atributos computados agregados a las representaciones del modelo
     *
     * Estos accessors se incluyen automáticamente en JSON/array.
     * IMPORTANTE: Puede afectar rendimiento en consultas masivas.
     * Considerar usar solo cuando sea necesario en APIs.
     *
     * @var array<string>
     */
    protected $appends = [
        'credito_disponible',          // Crédito disponible calculado
        'tiene_credito_disponible',    // Booleano si tenemos crédito disponible
        'porcentaje_credito_usado',    // Porcentaje de uso del crédito
        'calificacion_texto',           // Texto legible de la calificación
    ];

    /**
     * Constantes para tipos de proveedor
     * Facilita el uso consistente y previene errores de escritura
     */
    public const TIPO_PRODUCTO = 'Producto';

    public const TIPO_SERVICIO = 'Servicio';

    public const TIPO_AMBOS = 'Ambos';

    /**
     * Constantes para calificaciones
     * Mapeo de valores numéricos a descripciones legibles
     */
    public const CALIFICACION_MALO = 1;

    public const CALIFICACION_REGULAR = 2;

    public const CALIFICACION_BUENO = 3;

    public const CALIFICACION_EXCELENTE = 4;

    public const CALIFICACION_SOBRESALIENTE = 5;

    /**
     * Mapeo de calificaciones numéricas a textos
     *
     * @var array<int, string>
     */
    public const CALIFICACIONES = [
        self::CALIFICACION_MALO => 'Malo',
        self::CALIFICACION_REGULAR => 'Regular',
        self::CALIFICACION_BUENO => 'Bueno',
        self::CALIFICACION_EXCELENTE => 'Excelente',
        self::CALIFICACION_SOBRESALIENTE => 'Sobresaliente',
    ];

    /**
     * Prefijo para generación de códigos de proveedor
     */
    public const CODIGO_PREFIJO = 'PROV';

    /*
    |--------------------------------------------------------------------------
    | RELACIONES ELOQUENT
    |--------------------------------------------------------------------------
    */

    /**
     * Relación inversa con Persona
     *
     * Un proveedor pertenece a una persona.
     * Permite acceder a los datos personales (nombre, documento, contacto, etc.)
     */
    public function persona(): BelongsTo
    {
        return $this->belongsTo(Persona::class, 'persona_id');
    }

    /**
     * Relación con Compras
     *
     * Un proveedor puede tener múltiples compras.
     * Preparado para el módulo de gestión de compras/órdenes de compra.
     *
     * @return HasMany
     */
    /*public function compras(): HasMany
    {
        return $this->hasMany(Compra::class, 'proveedor_id');
    }*/

    /*
    |--------------------------------------------------------------------------
    | QUERY SCOPES
    |--------------------------------------------------------------------------
    */

    /**
     * Scope para filtrar solo proveedores activos
     *
     * Uso: Proveedor::activos()->get()
     */
    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('estado', true);
    }

    /**
     * Scope para filtrar por tipo de proveedor
     *
     * Uso: Proveedor::porTipo('Producto')->get()
     */
    public function scopePorTipo(Builder $query, string $tipo): Builder
    {
        return $query->where('tipo_proveedor', $tipo);
    }

    /**
     * Scope para filtrar por calificación
     *
     * Uso: Proveedor::porCalificacion(5)->get()
     */
    public function scopePorCalificacion(Builder $query, int $calificacion): Builder
    {
        return $query->where('calificacion', $calificacion);
    }

    /**
     * Scope para filtrar por rubro/industria
     *
     * Uso: Proveedor::porRubro('Tecnología')->get()
     */
    public function scopePorRubro(Builder $query, string $rubro): Builder
    {
        return $query->where('rubro', 'LIKE', "%{$rubro}%");
    }

    /**
     * Scope para proveedores con crédito disponible
     *
     * Filtra proveedores que nos otorgan crédito y aún tenemos saldo disponible
     * Uso: Proveedor::conCredito()->get()
     */
    public function scopeConCredito(Builder $query): Builder
    {
        return $query->where('limite_credito', '>', 0)
            ->whereRaw('credito_usado < limite_credito');
    }

    /**
     * Scope para proveedores mejor calificados (4 o 5 estrellas)
     *
     * Uso: Proveedor::mejorCalificados()->get()
     */
    public function scopeMejorCalificados(Builder $query): Builder
    {
        return $query->where('calificacion', '>=', self::CALIFICACION_EXCELENTE);
    }

    /**
     * Scope para incluir datos de la persona relacionada
     *
     * Optimiza consultas con eager loading
     * Uso: Proveedor::conPersona()->get()
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
     * Retorna el saldo de crédito que el proveedor aún nos otorga.
     * Fórmula: límite_credito - credito_usado
     *
     * IMPORTANTE: Es el crédito que NOSOTROS podemos usar, no el que otorgamos
     */
    public function getCreditoDisponibleAttribute(): float
    {
        return max(0, $this->limite_credito - $this->credito_usado);
    }

    /**
     * Accessor para verificar si tenemos crédito disponible
     *
     * Retorna true si el proveedor nos otorga crédito y aún tenemos saldo
     */
    public function getTieneCreditoDisponibleAttribute(): bool
    {
        return $this->credito_disponible > 0 && $this->dias_credito > 0;
    }

    /**
     * Accessor para calcular el porcentaje de crédito usado
     *
     * Retorna el porcentaje de uso del límite de crédito (0-100)
     * Útil para alertas y gestión de flujo de caja
     */
    public function getPorcentajeCreditoUsadoAttribute(): float
    {
        if ($this->limite_credito == 0) {
            return 0;
        }

        return round(($this->credito_usado / $this->limite_credito) * 100, 2);
    }

    /**
     * Accessor para obtener el texto de la calificación
     *
     * Convierte el valor numérico (1-5) en texto legible
     * Ejemplo: 5 -> "Sobresaliente"
     */
    public function getCalificacionTextoAttribute(): string
    {
        return self::CALIFICACIONES[$this->calificacion] ?? 'Sin calificar';
    }

    /*
    |--------------------------------------------------------------------------
    | MÉTODOS ESTÁTICOS
    |--------------------------------------------------------------------------
    */

    /**
     * Genera un código único automático para el proveedor
     *
     * Formato: PROV000001, PROV000002, etc.
     * Usa transacción y lockForUpdate para evitar códigos duplicados en concurrencia
     *
     * @return string Código generado
     */
    public static function generarCodigo(): string
    {
        return DB::transaction(function () {
            // Obtener el último proveedor creado con bloqueo
            $ultimo = self::lockForUpdate()->orderBy('id', 'desc')->first();

            // Extraer número del código y sumar 1
            $numero = $ultimo ? (int) substr($ultimo->codigo, \strlen(self::CODIGO_PREFIJO)) + 1 : 1;

            // Formatear código con padding de ceros (6 dígitos)
            return self::CODIGO_PREFIJO.\str_pad($numero, 6, '0', STR_PAD_LEFT);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | MÉTODOS DE NEGOCIO - GESTIÓN DE CRÉDITO
    |--------------------------------------------------------------------------
    */

    /**
     * Verifica si podemos comprar a crédito a este proveedor
     *
     * Valida que:
     * - El proveedor nos otorgue crédito disponible suficiente
     * - Tenga días de crédito configurados
     * - El proveedor esté activo
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
     * Usa (consume) crédito del proveedor
     *
     * Incrementa el crédito usado cuando realizamos una compra a crédito.
     * Valida que no exceda el límite que el proveedor nos otorga.
     *
     * @param  float  $monto  Monto a descontar del crédito disponible
     * @return bool True si se aplicó correctamente
     *
     * @throws \Exception Si el monto excede el crédito disponible
     */
    public function usarCredito(float $monto): bool
    {
        // Validar que no exceda el límite otorgado por el proveedor
        if ($this->credito_usado + $monto > $this->limite_credito) {
            throw new \Exception(
                "El monto ({$monto}) excede el crédito disponible ({$this->credito_disponible}) ".
                "que nos otorga el proveedor {$this->codigo}"
            );
        }

        // Incrementar crédito usado
        $this->credito_usado += $monto;

        return $this->save();
    }

    /**
     * Libera (devuelve) crédito del proveedor
     *
     * Decrementa el crédito usado cuando pagamos una deuda al proveedor.
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

    /*
    |--------------------------------------------------------------------------
    | MÉTODOS DE NEGOCIO - GESTIÓN DE HISTORIAL
    |--------------------------------------------------------------------------
    */

    /**
     * Actualiza la fecha de última compra
     *
     * Debe llamarse cada vez que se confirma una orden de compra
     *
     * @param  \Carbon\Carbon|string|null  $fecha  Fecha de la compra (default: hoy)
     */
    public function actualizarUltimaCompra($fecha = null): bool
    {
        $this->ultima_compra = $fecha ?? now();

        return $this->save();
    }

    /**
     * Incrementa el total de compras al proveedor
     *
     * Se ejecuta al confirmar una orden de compra
     *
     * @param  float  $monto  Monto de la compra a sumar
     */
    public function incrementarTotalCompras(float $monto): bool
    {
        $this->total_compras += $monto;

        return $this->save();
    }

    /**
     * Actualiza la calificación del proveedor
     *
     * Permite actualizar manualmente la calificación del proveedor.
     * En el futuro, se puede automatizar basándose en:
     * - Cumplimiento de plazos de entrega
     * - Calidad de productos/servicios
     * - Servicio postventa
     *
     * @param  int  $calificacion  Nueva calificación (1-5)
     *
     * @throws \InvalidArgumentException Si la calificación no está en rango 1-5
     */
    public function actualizarCalificacion(int $calificacion): bool
    {
        // Validar rango
        if ($calificacion < 1 || $calificacion > 5) {
            throw new \InvalidArgumentException(
                "La calificación debe estar entre 1 y 5. Recibido: {$calificacion}"
            );
        }

        $this->calificacion = $calificacion;

        return $this->save();
    }

    /*
    |--------------------------------------------------------------------------
    | MÉTODOS AUXILIARES
    |--------------------------------------------------------------------------
    */

    /**
     * Verifica si el proveedor es de productos
     */
    public function esProducto(): bool
    {
        return $this->tipo_proveedor === self::TIPO_PRODUCTO
            || $this->tipo_proveedor === self::TIPO_AMBOS;
    }

    /**
     * Verifica si el proveedor es de servicios
     */
    public function esServicio(): bool
    {
        return $this->tipo_proveedor === self::TIPO_SERVICIO
            || $this->tipo_proveedor === self::TIPO_AMBOS;
    }

    /**
     * Verifica si el proveedor tiene buena calificación (>= 4)
     */
    public function tieneBuenaCalificacion(): bool
    {
        return $this->calificacion >= self::CALIFICACION_EXCELENTE;
    }

    /**
     * Verifica si el proveedor está inactivo (sin compras recientes)
     *
     * Considera inactivo si no hay compras en los últimos X días
     *
     * @param  int  $dias  Días de inactividad a considerar (default: 90)
     */
    public function estaInactivo(int $dias = 90): bool
    {
        if (! $this->ultima_compra) {
            return true;
        }

        return $this->ultima_compra->diffInDays(now()) > $dias;
    }

    /**
     * Obtiene el nombre completo del proveedor desde la persona relacionada
     */
    public function getNombreCompleto(): string
    {
        return $this->persona->nombre_completo ?? 'Sin nombre';
    }

    /**
     * Obtiene el número de documento del proveedor
     */
    public function getNumeroDocumento(): string
    {
        return $this->persona->numero_documento ?? '';
    }

    /**
     * Obtiene información del contacto comercial formateada
     */
    public function getInfoContacto(): string
    {
        if (! $this->nombre_contacto) {
            return 'Sin contacto asignado';
        }

        $info = $this->nombre_contacto;

        if ($this->cargo_contacto) {
            $info .= " ({$this->cargo_contacto})";
        }

        return $info;
    }
}
