<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Permiso;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Controlador API para gestión de Permisos
 */
class PermisoController extends Controller
{
    /**
     * Listar todos los permisos
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Permiso::query();

            if ($request->filled('modulo')) {
                $query->where('modulo', $request->modulo);
            }

            if ($request->has('search') && $request->search != '') {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('codigo', 'ilike', "%{$search}%")
                        ->orWhere('nombre', 'ilike', "%{$search}%");
                });
            }

            $query->orderBy('modulo')->orderBy('nombre');

            // DataTables maneja la paginación, por defecto retornamos todos
            $permisos = $query->get();

            return response()->json([
                'success' => true,
                'data' => $permisos,
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los permisos',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Crear un nuevo permiso
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'codigo' => 'required|string|max:100|unique:permisos,codigo',
                'nombre' => 'required|string|max:150',
                'modulo' => 'required|string|max:50',
                'descripcion' => 'nullable|string|max:500',
            ], [
                'codigo.required' => 'El código es obligatorio',
                'codigo.unique' => 'El código ya existe',
                'nombre.required' => 'El nombre es obligatorio',
                'modulo.required' => 'El módulo es obligatorio',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $permiso = Permiso::create($request->only(['codigo', 'nombre', 'modulo', 'descripcion']));

            return response()->json([
                'success' => true,
                'message' => 'Permiso creado exitosamente',
                'data' => $permiso,
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el permiso',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mostrar un permiso específico
     */
    public function show(int $id): JsonResponse
    {
        try {
            $permiso = Permiso::with('roles')->find($id);

            if (! $permiso) {
                return response()->json([
                    'success' => false,
                    'message' => 'Permiso no encontrado',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $permiso,
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el permiso',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Actualizar un permiso
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $permiso = Permiso::find($id);

            if (! $permiso) {
                return response()->json([
                    'success' => false,
                    'message' => 'Permiso no encontrado',
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'codigo' => 'required|string|max:100|unique:permisos,codigo,'.$id,
                'nombre' => 'required|string|max:150',
                'modulo' => 'required|string|max:50',
                'descripcion' => 'nullable|string|max:500',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $permiso->update($request->only(['codigo', 'nombre', 'modulo', 'descripcion']));

            return response()->json([
                'success' => true,
                'message' => 'Permiso actualizado exitosamente',
                'data' => $permiso,
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el permiso',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Eliminar un permiso
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $permiso = Permiso::find($id);

            if (! $permiso) {
                return response()->json([
                    'success' => false,
                    'message' => 'Permiso no encontrado',
                ], 404);
            }

            $permiso->roles()->detach();
            $permiso->delete();

            return response()->json([
                'success' => true,
                'message' => 'Permiso eliminado exitosamente',
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el permiso',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener módulos disponibles
     */
    public function getModulos(): JsonResponse
    {
        try {
            $modulos = Permiso::getModulos();

            return response()->json([
                'success' => true,
                'data' => $modulos,
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener módulos',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener permisos agrupados por módulo
     */
    public function getAgrupados(): JsonResponse
    {
        try {
            $permisos = Permiso::orderBy('modulo')->orderBy('nombre')->get();
            $agrupados = $permisos->groupBy('modulo');

            return response()->json([
                'success' => true,
                'data' => $agrupados,
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener permisos agrupados',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
