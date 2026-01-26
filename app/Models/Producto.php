<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{
    use HasFactory;

    protected $table = 'productos';

    protected $fillable = [
        'codigo',
        'nombre',
        'descripcion',
        'categoria_id',
        'precio_compra',
        'precio_venta',
        'stock',
        'stock_minimo',
        'unidad_medida',
        'imagen',
        'estado',
    ];

    protected $casts = [
        'precio_compra' => 'decimal:2',
        'precio_venta' => 'decimal:2',
        'stock' => 'integer',
        'stock_minimo' => 'integer',
        'estado' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = ['margen_utilidad', 'tiene_stock_bajo'];

    // Relación con categoría
    public function categoria()
    {
        return $this->belongsTo(Categoria::class);
    }

    // Scope para productos activos
    public function scopeActivos($query)
    {
        return $query->where('estado', true);
    }

    // Scope para productos inactivos
    public function scopeInactivos($query)
    {
        return $query->where('estado', false);
    }

    // Scope para productos con stock bajo
    public function scopeStockBajo($query)
    {
        return $query->whereColumn('stock', '<=', 'stock_minimo');
    }

    // Accessor para margen de utilidad
    public function getMargenUtilidadAttribute()
    {
        if ($this->precio_compra > 0) {
            return round((($this->precio_venta - $this->precio_compra) / $this->precio_compra) * 100, 2);
        }

        return 0;
    }

    // Accessor para verificar stock bajo
    public function getTieneStockBajoAttribute()
    {
        return $this->stock <= $this->stock_minimo;
    }

    // Generar código automático
    public static function generarCodigo()
    {
        $ultimo = self::orderBy('id', 'desc')->first();
        $numero = $ultimo ? (int) substr($ultimo->codigo, 4) + 1 : 1;

        return 'PROD'.str_pad($numero, 6, '0', STR_PAD_LEFT);
    }
}
