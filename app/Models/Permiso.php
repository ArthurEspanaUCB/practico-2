<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Modelo Permiso
 *
 * Representa un permiso del sistema.
 * Un permiso es una acción específica sobre un módulo.
 */
class Permiso extends Model
{
    use HasFactory;

    protected $table = 'permisos';

    protected $fillable = [
        'codigo',
        'nombre',
        'modulo',
        'descripcion',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Módulos del sistema
    public const MODULO_CATEGORIAS = 'categorias';

    public const MODULO_PRODUCTOS = 'productos';

    public const MODULO_CLIENTES = 'clientes';

    public const MODULO_PROVEEDORES = 'proveedores';

    public const MODULO_COMPRAS = 'compras';

    public const MODULO_VENTAS = 'ventas';

    public const MODULO_USUARIOS = 'usuarios';

    public const MODULO_ROLES = 'roles';

    public const MODULO_REPORTES = 'reportes';

    /*
    |--------------------------------------------------------------------------
    | RELACIONES ELOQUENT
    |--------------------------------------------------------------------------
    */

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Rol::class, 'rol_permiso', 'permiso_id', 'rol_id')
            ->withTimestamps();
    }

    /*
    |--------------------------------------------------------------------------
    | QUERY SCOPES
    |--------------------------------------------------------------------------
    */

    public function scopePorModulo(Builder $query, string $modulo): Builder
    {
        return $query->where('modulo', $modulo);
    }

    /*
    |--------------------------------------------------------------------------
    | MÉTODOS ESTÁTICOS
    |--------------------------------------------------------------------------
    */

    /**
     * Obtiene todos los módulos disponibles
     */
    public static function getModulos(): array
    {
        return [
            self::MODULO_CATEGORIAS => 'Categorías',
            self::MODULO_PRODUCTOS => 'Productos',
            self::MODULO_CLIENTES => 'Clientes',
            self::MODULO_PROVEEDORES => 'Proveedores',
            self::MODULO_COMPRAS => 'Compras',
            self::MODULO_VENTAS => 'Ventas',
            self::MODULO_USUARIOS => 'Usuarios',
            self::MODULO_ROLES => 'Roles',
            self::MODULO_REPORTES => 'Reportes',
        ];
    }

    /**
     * Obtiene permisos agrupados por módulo
     */
    public static function agrupadosPorModulo(): array
    {
        return self::all()->groupBy('modulo')->toArray();
    }
}
