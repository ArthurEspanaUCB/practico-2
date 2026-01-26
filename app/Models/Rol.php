<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Modelo Rol
 *
 * Representa un rol del sistema que agrupa permisos.
 * Los usuarios pueden tener múltiples roles.
 */
class Rol extends Model
{
    use HasFactory;

    protected $table = 'roles';

    protected $fillable = [
        'codigo',
        'nombre',
        'descripcion',
        'estado',
    ];

    protected $casts = [
        'estado' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = [
        'cantidad_permisos',
        'cantidad_usuarios',
    ];

    public const CODIGO_PREFIJO = 'ROL';

    /*
    |--------------------------------------------------------------------------
    | RELACIONES ELOQUENT
    |--------------------------------------------------------------------------
    */

    public function permisos(): BelongsToMany
    {
        return $this->belongsToMany(Permiso::class, 'rol_permiso', 'rol_id', 'permiso_id')
            ->withTimestamps();
    }

    public function usuarios(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'rol_usuario', 'rol_id', 'user_id')
            ->withTimestamps();
    }

    /*
    |--------------------------------------------------------------------------
    | QUERY SCOPES
    |--------------------------------------------------------------------------
    */

    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('estado', true);
    }

    public function scopeInactivos(Builder $query): Builder
    {
        return $query->where('estado', false);
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS (GETTERS)
    |--------------------------------------------------------------------------
    */

    public function getCantidadPermisosAttribute(): int
    {
        return $this->permisos()->count();
    }

    public function getCantidadUsuariosAttribute(): int
    {
        return $this->usuarios()->count();
    }

    /*
    |--------------------------------------------------------------------------
    | MÉTODOS ESTÁTICOS
    |--------------------------------------------------------------------------
    */

    public static function generarCodigo(): string
    {
        $ultimo = self::orderBy('id', 'desc')->first();
        $numero = $ultimo ? (int) substr($ultimo->codigo, strlen(self::CODIGO_PREFIJO)) + 1 : 1;

        return self::CODIGO_PREFIJO.str_pad($numero, 4, '0', STR_PAD_LEFT);
    }

    /*
    |--------------------------------------------------------------------------
    | MÉTODOS DE NEGOCIO
    |--------------------------------------------------------------------------
    */

    /**
     * Verifica si el rol tiene un permiso específico
     */
    public function tienePermiso(string $codigoPermiso): bool
    {
        return $this->permisos()->where('codigo', $codigoPermiso)->exists();
    }

    /**
     * Asigna permisos al rol (reemplaza los existentes)
     */
    public function asignarPermisos(array $permisoIds): void
    {
        $this->permisos()->sync($permisoIds);
    }

    /**
     * Agrega permisos al rol (sin reemplazar)
     */
    public function agregarPermisos(array $permisoIds): void
    {
        $this->permisos()->syncWithoutDetaching($permisoIds);
    }

    /**
     * Quita un permiso del rol
     */
    public function quitarPermiso(int $permisoId): void
    {
        $this->permisos()->detach($permisoId);
    }
}
