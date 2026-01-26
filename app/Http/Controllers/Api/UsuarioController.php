<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Rol;
use App\Models\User;
use App\Services\AuditLogger;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Controlador API para gestión de Usuarios
 */
class UsuarioController extends Controller
{
    /**
     * Listar todos los usuarios
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = User::with('roles');

            if ($request->filled('estado')) {
                $query->where('estado', $request->estado === 'true' || $request->estado === '1');
            }

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'ilike', "%{$search}%")
                        ->orWhere('email', 'ilike', "%{$search}%");
                });
            }

            $query->orderBy('id', 'desc');

            // DataTables maneja la paginación
            $usuarios = $query->get();

            // Log de consulta
            // AuditLogger::consulta("Consulta de usuarios. Filtros: " . json_encode($request->all()));

            return response()->json([
                'success' => true,
                'data' => $usuarios,
            ], 200);
        } catch (Exception $e) {
            AuditLogger::error('Error al obtener usuarios', $e);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los usuarios',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Crear un nuevo usuario
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|email|max:255|unique:users,email',
                'password' => 'required|string|min:6|confirmed',
                'estado' => 'nullable|boolean',
                'roles' => 'nullable|array',
                'roles.*' => 'exists:roles,id',
            ], [
                'name.required' => 'El nombre es obligatorio',
                'email.required' => 'El correo electrónico es obligatorio',
                'email.email' => 'Ingrese un correo electrónico válido',
                'email.unique' => 'Este correo electrónico ya está registrado',
                'password.required' => 'La contraseña es obligatoria',
                'password.min' => 'La contraseña debe tener al menos 6 caracteres',
                'password.confirmed' => 'Las contraseñas no coinciden',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $usuario = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => $request->password,
                'estado' => $request->estado ?? true,
            ]);

            if ($request->has('roles') && is_array($request->roles)) {
                $usuario->asignarRoles($request->roles);
            }

            $usuario->load('roles');

            AuditLogger::insercion("Usuario creado: {$usuario->email}", $request->except(['password', 'password_confirmation']));

            return response()->json([
                'success' => true,
                'message' => 'Usuario creado exitosamente',
                'data' => $usuario,
            ], 201);
        } catch (Exception $e) {
            AuditLogger::error('Error al crear usuario', $e);

            return response()->json([
                'success' => false,
                'message' => 'Error al crear el usuario',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mostrar un usuario específico
     */
    public function show(int $id): JsonResponse
    {
        try {
            $usuario = User::with('roles.permisos')->find($id);

            if (! $usuario) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no encontrado',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $usuario,
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el usuario',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Actualizar un usuario
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $usuario = User::find($id);

            if (! $usuario) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no encontrado',
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|email|max:255|unique:users,email,'.$id,
                'password' => 'nullable|string|min:6|confirmed',
                'estado' => 'nullable|boolean',
                'roles' => 'nullable|array',
                'roles.*' => 'exists:roles,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $datos = [
                'name' => $request->name,
                'email' => $request->email,
                'estado' => $request->estado ?? $usuario->estado,
            ];

            if ($request->filled('password')) {
                $datos['password'] = $request->password;
            }

            $usuario->update($datos);

            if ($request->has('roles') && is_array($request->roles)) {
                $usuario->asignarRoles($request->roles);
            }

            $usuario->load('roles');

            AuditLogger::actualizacion("Usuario actualizado: {$usuario->email}", $request->except(['password', 'password_confirmation']));

            return response()->json([
                'success' => true,
                'message' => 'Usuario actualizado exitosamente',
                'data' => $usuario,
            ], 200);
        } catch (Exception $e) {
            AuditLogger::error("Error al actualizar usuario ID {$id}", $e);

            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el usuario',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Asignar roles a un usuario
     */
    public function asignarRoles(Request $request, int $id): JsonResponse
    {
        try {
            $usuario = User::find($id);

            if (! $usuario) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no encontrado',
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'roles' => 'required|array',
                'roles.*' => 'exists:roles,id',
            ], [
                'roles.required' => 'Debe enviar la lista de roles',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $usuario->asignarRoles($request->roles);
            $usuario->load('roles');

            AuditLogger::actualizacion("Roles asignados a usuario {$usuario->email}: ".implode(',', $request->roles));

            return response()->json([
                'success' => true,
                'message' => 'Roles asignados exitosamente',
                'data' => $usuario,
            ], 200);
        } catch (Exception $e) {
            AuditLogger::error("Error al asignar roles usuario ID {$id}", $e);

            return response()->json([
                'success' => false,
                'message' => 'Error al asignar roles',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cambiar estado del usuario
     */
    public function toggleEstado(int $id): JsonResponse
    {
        try {
            $usuario = User::find($id);

            if (! $usuario) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no encontrado',
                ], 404);
            }

            $usuario->estado = ! $usuario->estado;
            $usuario->save();

            AuditLogger::actualizacion("Cambio estado usuario {$usuario->email} a ".($usuario->estado ? 'Activo' : 'Inactivo'));

            return response()->json([
                'success' => true,
                'message' => $usuario->estado ? 'Usuario activado' : 'Usuario desactivado',
                'data' => $usuario,
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
     * Eliminar un usuario
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $usuario = User::find($id);

            if (! $usuario) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no encontrado',
                ], 404);
            }

            $usuario->roles()->detach();
            $usuario->delete();

            AuditLogger::eliminacion("Usuario eliminado ID {$id} ({$usuario->email})");

            return response()->json([
                'success' => true,
                'message' => 'Usuario eliminado exitosamente',
            ], 200);
        } catch (Exception $e) {
            AuditLogger::error("Error al eliminar usuario ID {$id}", $e);

            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el usuario',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener lista de roles activos
     */
    public function getRoles(): JsonResponse
    {
        try {
            $roles = Rol::activos()->orderBy('nombre')->get();

            return response()->json([
                'success' => true,
                'data' => $roles,
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener roles',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
