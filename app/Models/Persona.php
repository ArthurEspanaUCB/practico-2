<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Modelo Persona
 *
 * Representa una persona (natural o jurídica) en el sistema.
 * Tabla base que centraliza información personal y es referenciada
 * por otras entidades como Cliente, Proveedor, etc.
 *
 * @property int $id
 * @property string $tipo_documento
 * @property string $numero_documento
 * @property string $nombres
 * @property string|null $apellidos
 * @property string|null $razon_social
 * @property string|null $email
 * @property string|null $telefono
 * @property string|null $celular
 * @property string|null $direccion
 * @property string|null $ciudad
 * @property string|null $provincia
 * @property string $pais
 * @property \Illuminate\Support\Carbon|null $fecha_nacimiento
 * @property string|null $sexo
 * @property bool $estado
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read string $nombre_completo Nombre completo o razón social
 * @property-read int|null $edad Edad calculada desde fecha de nacimiento
 * @property-read Cliente|null $cliente Relación con cliente
 * @property-read Proveedor|null $proveedor Relación con proveedor
 *
 * @method static Builder activas() Scope para filtrar personas activas
 */
class Persona extends Model
{
    use HasFactory;

    /**
     * Nombre de la tabla asociada al modelo
     *
     * @var string
     */
    protected $table = 'personas';

    /**
     * Atributos asignables en masa
     *
     * Define los campos que pueden ser llenados mediante asignación masiva
     * para protección contra vulnerabilidades de asignación masiva
     *
     * @var array<string>
     */
    protected $fillable = [
        'tipo_documento',      // Tipo de documento (DNI, RUC, CE, PASAPORTE)
        'numero_documento',    // Número único del documento
        'nombres',             // Nombres de la persona
        'apellidos',           // Apellidos (nullable para personas jurídicas)
        'razon_social',        // Razón social para RUC
        'email',               // Correo electrónico
        'telefono',            // Teléfono fijo
        'celular',             // Teléfono celular
        'direccion',           // Dirección completa
        'ciudad',              // Ciudad de residencia
        'provincia',           // Provincia o departamento
        'pais',                // País (default: Bolivia)
        'fecha_nacimiento',    // Fecha de nacimiento
        'sexo',                // Sexo (M, F, Otro)
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
        'fecha_nacimiento' => 'date',      // Convierte a Carbon date
        'estado' => 'boolean',             // Convierte a booleano
        'created_at' => 'datetime',        // Convierte a Carbon datetime
        'updated_at' => 'datetime',         // Convierte a Carbon datetime
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
        'nombre_completo',    // Nombre completo calculado
        'edad',                // Edad calculada desde fecha_nacimiento
    ];

    /*
    |--------------------------------------------------------------------------
    | RELACIONES ELOQUENT
    |--------------------------------------------------------------------------
    */

    /**
     * Relación uno a uno con Cliente
     *
     * Una persona puede ser registrada como cliente.
     * Esta relación permite acceder a la información comercial del cliente.
     */
    public function cliente(): HasOne
    {
        return $this->hasOne(Cliente::class, 'persona_id');
    }

    /**
     * Relación uno a uno con Proveedor
     *
     * Una persona puede ser registrada como proveedor.
     * Preparado para futuro módulo de gestión de proveedores.
     */
    public function proveedor(): HasOne
    {
        return $this->hasOne(Proveedor::class, 'persona_id');
    }

    /*
    |--------------------------------------------------------------------------
    | QUERY SCOPES
    |--------------------------------------------------------------------------
    */

    /**
     * Scope para filtrar solo personas activas
     *
     * Uso: Persona::activas()->get()
     */
    public function scopeActivas(Builder $query): Builder
    {
        return $query->where('estado', true);
    }

    /**
     * Scope para filtrar por tipo de documento
     *
     * Uso: Persona::porTipoDocumento('DNI')->get()
     */
    public function scopePorTipoDocumento(Builder $query, string $tipo): Builder
    {
        return $query->where('tipo_documento', $tipo);
    }

    /**
     * Scope para buscar por número de documento
     *
     * Uso: Persona::porNumeroDocumento('12345678')->first()
     */
    public function scopePorNumeroDocumento(Builder $query, string $numero): Builder
    {
        return $query->where('numero_documento', $numero);
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS (GETTERS)
    |--------------------------------------------------------------------------
    */

    /**
     * Accessor para obtener el nombre completo
     *
     * Retorna la razón social si es persona jurídica (RUC),
     * o concatena nombres y apellidos si es persona natural.
     */
    public function getNombreCompletoAttribute(): string
    {
        // Para personas jurídicas (RUC), usar razón social
        if ($this->tipo_documento === 'RUC' && $this->razon_social) {
            return $this->razon_social;
        }

        // Para personas naturales, concatenar nombres y apellidos
        return trim($this->nombres.' '.($this->apellidos ?? ''));
    }

    /**
     * Accessor para calcular la edad en años
     *
     * Calcula la edad basándose en la fecha de nacimiento.
     * Retorna null si no hay fecha de nacimiento registrada.
     *
     * @return int|null Edad en años o null
     */
    public function getEdadAttribute(): ?int
    {
        if (! $this->fecha_nacimiento) {
            return null;
        }

        // Carbon proporciona el método age para calcular años
        return $this->fecha_nacimiento->age;
    }

    /*
    |--------------------------------------------------------------------------
    | MÉTODOS AUXILIARES
    |--------------------------------------------------------------------------
    */

    /**
     * Verifica si la persona es natural (individuo)
     *
     * Las personas naturales tienen DNI, Carné de Extranjería o Pasaporte.
     */
    public function esPersonaNatural(): bool
    {
        return in_array($this->tipo_documento, ['DNI', 'CE', 'PASAPORTE']);
    }

    /**
     * Verifica si la persona es jurídica (empresa/organización)
     *
     * Las personas jurídicas tienen RUC (Registro Único de Contribuyentes).
     */
    public function esPersonaJuridica(): bool
    {
        return $this->tipo_documento === 'RUC';
    }

    /**
     * Obtiene el nombre para mostrar según el tipo de persona
     */
    public function getNombreDisplay(): string
    {
        return $this->nombre_completo;
    }

    /**
     * Verifica si la persona tiene información de contacto completa
     */
    public function tieneContactoCompleto(): bool
    {
        return ! empty($this->email) || ! empty($this->telefono) || ! empty($this->celular);
    }

    /**
     * Obtiene la descripción del tipo de documento
     */
    public function getTipoDocumentoDescripcion(): string
    {
        return match ($this->tipo_documento) {
            'DNI' => 'Documento Nacional de Identidad',
            'RUC' => 'Registro Único de Contribuyentes',
            'CE' => 'Carné de Extranjería',
            'PASAPORTE' => 'Pasaporte',
            default => $this->tipo_documento
        };
    }
}
