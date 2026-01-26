<?php

namespace Database\Seeders;

use App\Models\Categoria;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategoriaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('categorias')->truncate();

        $categorias = [
            [
                'nombre' => 'Electrónicos',
                'descripcion' => 'Productos electrónicos y tecnológicos',
                'estado' => true,
            ],
            [
                'nombre' => 'Ropa y Moda',
                'descripcion' => 'Prendas de vestir y accesorios de moda',
                'estado' => true,
            ],
            [
                'nombre' => 'Hogar y Jardín',
                'descripcion' => 'Artículos para el hogar y jardín',
                'estado' => true,
            ],
            [
                'nombre' => 'Deportes',
                'descripcion' => 'Equipamiento y accesorios deportivos',
                'estado' => true,
            ],
            [
                'nombre' => 'Alimentos y Bebidas',
                'descripcion' => 'Productos alimenticios y bebidas',
                'estado' => true,
            ],
            [
                'nombre' => 'Libros',
                'descripcion' => 'Libros, revistas y material de lectura',
                'estado' => false,
            ],
            [
                'nombre' => 'Juguetes',
                'descripcion' => 'Juguetes y juegos para niños',
                'estado' => true,
            ],
            [
                'nombre' => 'Belleza y Cuidado Personal',
                'descripcion' => 'Productos de belleza y cuidado personal',
                'estado' => true,
            ],
        ];

        foreach ($categorias as $categoria) {
            Categoria::create($categoria);
        }
    }
}
