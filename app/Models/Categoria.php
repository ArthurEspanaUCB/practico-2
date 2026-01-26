<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Categoria extends Model
{
    use HasFactory;

    protected $table = 'categorias';

    protected $fillable = [
        'nombre',
        'descripcion',
        'estado',
    ];

    protected $casts = [
        'estado' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function scopeActivas($query)
    {
        return $query->where('estado', true);
    }

    public function scopeInactivas($query)
    {
        return $query->where('estado', false);
    }

    public function productos()
    {
        return $this->hasMany(Producto::class);
    }
}
