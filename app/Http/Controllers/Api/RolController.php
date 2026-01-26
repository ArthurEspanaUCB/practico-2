<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Permiso;
use App\Models\Rol;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Controlador API para gestión de Roles
 */
class RolController extends Controller
{
    /**
     * Listar todos los roles
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Rol::with('permisos');

            if ($request->filled('estado')) {
                $query->where('estado', $request->estado === 'true' || $request->estado === '1');
            }

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('codigo', 'ilike', "%{$search}%")
                        ->orWhere('nombre', 'ilike', "%{$search}%");
                });
            }

            $query->orderBy('id', 'desc');

            // DataTables maneja la paginación
            $roles = $query->get();

            return response()->json([
                'success' => true,
                'data' => $roles,
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los roles',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Crear un nuevo rol
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'nombre' => 'required|string|max:100',
                'descripcion' => 'nullable|string|max:500',
                'estado' => 'nullable|boolean',
                'permisos' => 'nullable|array',
                'permisos.*' => 'exists:permisos,id',
            ], [
                'nombre.required' => 'El nombre del rol es obligatorio',
                'nombre.max' => 'El nombre no puede exceder 100 caracteres',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $codigo = Rol::generarCodigo();

            $rol = Rol::create([
                'codigo' => $codigo,
                'nombre' => $request->nombre,
                'descripcion' => $request->descripcion,
                'estado' => $request->estado ?? true,
            ]);

            if ($request->has('permisos') && is_array($request->permisos)) {
                $rol->asignarPermisos($request->permisos);
            }

            $rol->load('permisos');

            return response()->json([
                'success' => true,
                'message' => 'Rol creado exitosamente',
                'data' => $rol,
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el rol',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mostrar un rol específico
     */
    public function show(int $id): JsonResponse
    {
        try {
            $rol = Rol::with('permisos')->find($id);

            if (! $rol) {
                return response()->json([
                    'success' => false,
                    'message' => 'Rol no encontrado',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $rol,
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el rol',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Actualizar un rol
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $rol = Rol::find($id);

            if (! $rol) {
                return response()->json([
                    'success' => false,
                    'message' => 'Rol no encontrado',
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'nombre' => 'required|string|max:100',
                'descripcion' => 'nullable|string|max:500',
                'estado' => 'nullable|boolean',
                'permisos' => 'nullable|array',
                'permisos.*' => 'exists:permisos,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $rol->update([
                'nombre' => $request->nombre,
                'descripcion' => $request->descripcion,
                'estado' => $request->estado ?? $rol->estado,
            ]);

            if ($request->has('permisos') && is_array($request->permisos)) {
                $rol->asignarPermisos($request->permisos);
            }

            $rol->load('permisos');

            return response()->json([
                'success' => true,
                'message' => 'Rol actualizado exitosamente',
                'data' => $rol,
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el rol',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Asignar permisos a un rol
     */
    public function asignarPermisos(Request $request, int $id): JsonResponse
    {
        try {
            $rol = Rol::find($id);

            if (! $rol) {
                return response()->json([
                    'success' => false,
                    'message' => 'Rol no encontrado',
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'permisos' => 'required|array',
                'permisos.*' => 'exists:permisos,id',
            ], [
                'permisos.required' => 'Debe enviar la lista de permisos',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $rol->asignarPermisos($request->permisos);
            $rol->load('permisos');

            return response()->json([
                'success' => true,
                'message' => 'Permisos asignados exitosamente',
                'data' => $rol,
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al asignar permisos',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cambiar estado del rol
     */
    public function toggleEstado(int $id): JsonResponse
    {
        try {
            $rol = Rol::find($id);

            if (! $rol) {
                return response()->json([
                    'success' => false,
                    'message' => 'Rol no encontrado',
                ], 404);
            }

            $rol->estado = ! $rol->estado;
            $rol->save();

            return response()->json([
                'success' => true,
                'message' => $rol->estado ? 'Rol activado' : 'Rol desactivado',
                'data' => $rol,
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cambiar estado',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Eliminar un rol
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $rol = Rol::find($id);

            if (! $rol) {
                return response()->json([
                    'success' => false,
                    'message' => 'Rol no encontrado',
                ], 404);
            }

            if ($rol->usuarios()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede eliminar el rol porque tiene usuarios asignados',
                ], 422);
            }

            $rol->permisos()->detach();
            $rol->delete();

            return response()->json([
                'success' => true,
                'message' => 'Rol eliminado exitosamente',
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el rol',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generar código automático
     */
    public function generarCodigo(): JsonResponse
    {
        try {
            $codigo = Rol::generarCodigo();

            return response()->json([
                'success' => true,
                'data' => ['codigo' => $codigo],
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al generar código',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener lista de permisos agrupados por módulo
     */
    public function getPermisos(): JsonResponse
    {
        try {
            $permisos = Permiso::orderBy('modulo')->orderBy('nombre')->get();
            $agrupados = $permisos->groupBy('modulo');

            return response()->json([
                'success' => true,
                'data' => [
                    'permisos' => $permisos,
                    'agrupados' => $agrupados,
                ],
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener permisos',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
