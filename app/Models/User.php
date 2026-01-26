<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * Modelo User
 *
 * Usuario del sistema con soporte para roles y permisos.
 */
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'estado',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'estado' => 'boolean',
    ];

    protected $appends = [
        'nombres_roles',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELACIONES ELOQUENT
    |--------------------------------------------------------------------------
    */

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Rol::class, 'rol_usuario', 'user_id', 'rol_id')
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

    public function getNombresRolesAttribute(): string
    {
        return $this->roles->pluck('nombre')->implode(', ');
    }

    /*
    |--------------------------------------------------------------------------
    | MÉTODOS DE NEGOCIO - ROLES
    |--------------------------------------------------------------------------
    */

    /**
     * Verifica si el usuario tiene un rol específico
     */
    public function tieneRol(string $codigoRol): bool
    {
        return $this->roles()->where('codigo', $codigoRol)->exists();
    }

    /**
     * Asigna roles al usuario (reemplaza los existentes)
     */
    public function asignarRoles(array $rolIds): void
    {
        $this->roles()->sync($rolIds);
    }

    /**
     * Agrega roles al usuario (sin reemplazar)
     */
    public function agregarRoles(array $rolIds): void
    {
        $this->roles()->syncWithoutDetaching($rolIds);
    }

    /**
     * Quita un rol del usuario
     */
    public function quitarRol(int $rolId): void
    {
        $this->roles()->detach($rolId);
    }

    /*
    |--------------------------------------------------------------------------
    | MÉTODOS DE NEGOCIO - PERMISOS
    |--------------------------------------------------------------------------
    */

    /**
     * Obtiene todos los permisos del usuario a través de sus roles
     */
    public function getPermisos()
    {
        return Permiso::whereHas('roles', function ($query) {
            $query->whereIn('roles.id', $this->roles->pluck('id'));
        })->get();
    }

    /**
     * Verifica si el usuario tiene un permiso específico
     */
    public function tienePermiso(string $codigoPermiso): bool
    {
        return $this->roles()
            ->whereHas('permisos', function ($query) use ($codigoPermiso) {
                $query->where('codigo', $codigoPermiso);
            })
            ->exists();
    }

    /**
     * Verifica si el usuario tiene alguno de los permisos indicados
     */
    public function tieneAlgunPermiso(array $codigosPermisos): bool
    {
        return $this->roles()
            ->whereHas('permisos', function ($query) use ($codigosPermisos) {
                $query->whereIn('codigo', $codigosPermisos);
            })
            ->exists();
    }

    /**
     * Verifica si el usuario tiene todos los permisos indicados
     */
    public function tieneTodosLosPermisos(array $codigosPermisos): bool
    {
        foreach ($codigosPermisos as $codigo) {
            if (! $this->tienePermiso($codigo)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Verifica si el usuario es administrador
     */
    public function esAdmin(): bool
    {
        return $this->tieneRol('ROL0001') || $this->tieneRol('ADMIN');
    }
}
