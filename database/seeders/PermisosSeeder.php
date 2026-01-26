<?php

namespace Database\Seeders;

use App\Models\Permiso;
use App\Models\Rol;
use Illuminate\Database\Seeder;

/**
 * Seeder para crear los permisos iniciales del sistema
 * y un rol de Administrador con todos los permisos.
 */
class PermisosSeeder extends Seeder
{
    public function run(): void
    {
        $permisos = [
            // Categorías
            ['codigo' => 'categorias.ver', 'nombre' => 'Ver Categorías', 'modulo' => 'categorias', 'descripcion' => 'Permite ver el listado de categorías'],
            ['codigo' => 'categorias.crear', 'nombre' => 'Crear Categorías', 'modulo' => 'categorias', 'descripcion' => 'Permite crear nuevas categorías'],
            ['codigo' => 'categorias.editar', 'nombre' => 'Editar Categorías', 'modulo' => 'categorias', 'descripcion' => 'Permite editar categorías existentes'],
            ['codigo' => 'categorias.eliminar', 'nombre' => 'Eliminar Categorías', 'modulo' => 'categorias', 'descripcion' => 'Permite eliminar categorías'],

            // Productos
            ['codigo' => 'productos.ver', 'nombre' => 'Ver Productos', 'modulo' => 'productos', 'descripcion' => 'Permite ver el listado de productos'],
            ['codigo' => 'productos.crear', 'nombre' => 'Crear Productos', 'modulo' => 'productos', 'descripcion' => 'Permite crear nuevos productos'],
            ['codigo' => 'productos.editar', 'nombre' => 'Editar Productos', 'modulo' => 'productos', 'descripcion' => 'Permite editar productos existentes'],
            ['codigo' => 'productos.eliminar', 'nombre' => 'Eliminar Productos', 'modulo' => 'productos', 'descripcion' => 'Permite eliminar productos'],

            // Clientes
            ['codigo' => 'clientes.ver', 'nombre' => 'Ver Clientes', 'modulo' => 'clientes', 'descripcion' => 'Permite ver el listado de clientes'],
            ['codigo' => 'clientes.crear', 'nombre' => 'Crear Clientes', 'modulo' => 'clientes', 'descripcion' => 'Permite crear nuevos clientes'],
            ['codigo' => 'clientes.editar', 'nombre' => 'Editar Clientes', 'modulo' => 'clientes', 'descripcion' => 'Permite editar clientes existentes'],
            ['codigo' => 'clientes.eliminar', 'nombre' => 'Eliminar Clientes', 'modulo' => 'clientes', 'descripcion' => 'Permite eliminar clientes'],

            // Proveedores
            ['codigo' => 'proveedores.ver', 'nombre' => 'Ver Proveedores', 'modulo' => 'proveedores', 'descripcion' => 'Permite ver el listado de proveedores'],
            ['codigo' => 'proveedores.crear', 'nombre' => 'Crear Proveedores', 'modulo' => 'proveedores', 'descripcion' => 'Permite crear nuevos proveedores'],
            ['codigo' => 'proveedores.editar', 'nombre' => 'Editar Proveedores', 'modulo' => 'proveedores', 'descripcion' => 'Permite editar proveedores existentes'],
            ['codigo' => 'proveedores.eliminar', 'nombre' => 'Eliminar Proveedores', 'modulo' => 'proveedores', 'descripcion' => 'Permite eliminar proveedores'],

            // Compras
            ['codigo' => 'compras.ver', 'nombre' => 'Ver Compras', 'modulo' => 'compras', 'descripcion' => 'Permite ver el listado de compras'],
            ['codigo' => 'compras.crear', 'nombre' => 'Crear Compras', 'modulo' => 'compras', 'descripcion' => 'Permite registrar nuevas compras'],
            ['codigo' => 'compras.editar', 'nombre' => 'Editar Compras', 'modulo' => 'compras', 'descripcion' => 'Permite editar compras pendientes'],
            ['codigo' => 'compras.eliminar', 'nombre' => 'Eliminar Compras', 'modulo' => 'compras', 'descripcion' => 'Permite eliminar compras pendientes'],
            ['codigo' => 'compras.completar', 'nombre' => 'Completar Compras', 'modulo' => 'compras', 'descripcion' => 'Permite marcar compras como completadas'],
            ['codigo' => 'compras.anular', 'nombre' => 'Anular Compras', 'modulo' => 'compras', 'descripcion' => 'Permite anular compras'],

            // Ventas
            ['codigo' => 'ventas.ver', 'nombre' => 'Ver Ventas', 'modulo' => 'ventas', 'descripcion' => 'Permite ver el listado de ventas'],
            ['codigo' => 'ventas.crear', 'nombre' => 'Crear Ventas', 'modulo' => 'ventas', 'descripcion' => 'Permite registrar nuevas ventas'],
            ['codigo' => 'ventas.editar', 'nombre' => 'Editar Ventas', 'modulo' => 'ventas', 'descripcion' => 'Permite editar ventas pendientes'],
            ['codigo' => 'ventas.eliminar', 'nombre' => 'Eliminar Ventas', 'modulo' => 'ventas', 'descripcion' => 'Permite eliminar ventas pendientes'],
            ['codigo' => 'ventas.completar', 'nombre' => 'Completar Ventas', 'modulo' => 'ventas', 'descripcion' => 'Permite marcar ventas como completadas'],
            ['codigo' => 'ventas.anular', 'nombre' => 'Anular Ventas', 'modulo' => 'ventas', 'descripcion' => 'Permite anular ventas'],

            // Usuarios
            ['codigo' => 'usuarios.ver', 'nombre' => 'Ver Usuarios', 'modulo' => 'usuarios', 'descripcion' => 'Permite ver el listado de usuarios'],
            ['codigo' => 'usuarios.crear', 'nombre' => 'Crear Usuarios', 'modulo' => 'usuarios', 'descripcion' => 'Permite crear nuevos usuarios'],
            ['codigo' => 'usuarios.editar', 'nombre' => 'Editar Usuarios', 'modulo' => 'usuarios', 'descripcion' => 'Permite editar usuarios existentes'],
            ['codigo' => 'usuarios.eliminar', 'nombre' => 'Eliminar Usuarios', 'modulo' => 'usuarios', 'descripcion' => 'Permite eliminar usuarios'],
            ['codigo' => 'usuarios.asignar_roles', 'nombre' => 'Asignar Roles', 'modulo' => 'usuarios', 'descripcion' => 'Permite asignar roles a usuarios'],

            // Roles
            ['codigo' => 'roles.ver', 'nombre' => 'Ver Roles', 'modulo' => 'roles', 'descripcion' => 'Permite ver el listado de roles'],
            ['codigo' => 'roles.crear', 'nombre' => 'Crear Roles', 'modulo' => 'roles', 'descripcion' => 'Permite crear nuevos roles'],
            ['codigo' => 'roles.editar', 'nombre' => 'Editar Roles', 'modulo' => 'roles', 'descripcion' => 'Permite editar roles existentes'],
            ['codigo' => 'roles.eliminar', 'nombre' => 'Eliminar Roles', 'modulo' => 'roles', 'descripcion' => 'Permite eliminar roles'],
            ['codigo' => 'roles.asignar_permisos', 'nombre' => 'Asignar Permisos', 'modulo' => 'roles', 'descripcion' => 'Permite asignar permisos a roles'],

            // Permisos
            ['codigo' => 'permisos.ver', 'nombre' => 'Ver Permisos', 'modulo' => 'permisos', 'descripcion' => 'Permite ver el listado de permisos'],
            ['codigo' => 'permisos.crear', 'nombre' => 'Crear Permisos', 'modulo' => 'permisos', 'descripcion' => 'Permite crear nuevos permisos'],
            ['codigo' => 'permisos.editar', 'nombre' => 'Editar Permisos', 'modulo' => 'permisos', 'descripcion' => 'Permite editar permisos existentes'],
            ['codigo' => 'permisos.eliminar', 'nombre' => 'Eliminar Permisos', 'modulo' => 'permisos', 'descripcion' => 'Permite eliminar permisos'],

            // Dashboard y Reportes
            ['codigo' => 'dashboard.ver', 'nombre' => 'Ver Dashboard', 'modulo' => 'dashboard', 'descripcion' => 'Permite acceder al dashboard'],
            ['codigo' => 'reportes.ver', 'nombre' => 'Ver Reportes', 'modulo' => 'reportes', 'descripcion' => 'Permite acceder a los reportes'],
            ['codigo' => 'reportes.exportar', 'nombre' => 'Exportar Reportes', 'modulo' => 'reportes', 'descripcion' => 'Permite exportar reportes'],
        ];

        // Crear permisos
        $permisosIds = [];
        foreach ($permisos as $permiso) {
            $p = Permiso::updateOrCreate(
                ['codigo' => $permiso['codigo']],
                $permiso
            );
            $permisosIds[] = $p->id;
        }

        $this->command->info('✓ Se crearon/actualizaron '.count($permisos).' permisos');

        // Crear rol Administrador con todos los permisos
        $rolAdmin = Rol::updateOrCreate(
            ['codigo' => 'ADMIN'],
            [
                'nombre' => 'Administrador',
                'descripcion' => 'Rol con acceso completo a todas las funciones del sistema',
                'estado' => true,
            ]
        );

        $rolAdmin->permisos()->sync($permisosIds);
        $this->command->info('✓ Rol Administrador creado con todos los permisos');

        // Crear rol Vendedor con permisos básicos
        $rolVendedor = Rol::updateOrCreate(
            ['codigo' => 'VENDEDOR'],
            [
                'nombre' => 'Vendedor',
                'descripcion' => 'Rol con permisos de ventas y consulta de productos/clientes',
                'estado' => true,
            ]
        );

        $permisosVendedor = Permiso::whereIn('codigo', [
            'dashboard.ver',
            'productos.ver',
            'clientes.ver',
            'clientes.crear',
            'clientes.editar',
            'ventas.ver',
            'ventas.crear',
            'ventas.editar',
            'ventas.completar',
        ])->pluck('id')->toArray();

        $rolVendedor->permisos()->sync($permisosVendedor);
        $this->command->info('✓ Rol Vendedor creado con permisos básicos');

        // Crear rol Almacenero
        $rolAlmacenero = Rol::updateOrCreate(
            ['codigo' => 'ALMACENERO'],
            [
                'nombre' => 'Almacenero',
                'descripcion' => 'Rol con permisos de gestión de inventario y compras',
                'estado' => true,
            ]
        );

        $permisosAlmacenero = Permiso::whereIn('codigo', [
            'dashboard.ver',
            'categorias.ver',
            'productos.ver',
            'productos.crear',
            'productos.editar',
            'proveedores.ver',
            'proveedores.crear',
            'proveedores.editar',
            'compras.ver',
            'compras.crear',
            'compras.editar',
            'compras.completar',
        ])->pluck('id')->toArray();

        $rolAlmacenero->permisos()->sync($permisosAlmacenero);
        $this->command->info('✓ Rol Almacenero creado con permisos de inventario');
    }
}
