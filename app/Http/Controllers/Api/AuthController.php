<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    /**
     * Iniciar sesión
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors(),
            ], 422);
        }

        if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
            $user = Auth::user();

            // Verificar si el usuario está activo
            if (! $user->estado) {
                Auth::logout();

                return response()->json([
                    'success' => false,
                    'message' => 'Su cuenta está inactiva. Contacte al administrador.',
                ], 403);
            }

            // Crear token
            $token = $user->createToken('auth_token')->plainTextToken;

            // Log de Login exitoso
            AuditLogger::login("Usuario {$user->email} inició sesión exitosamente.");

            // Obtener roles y permisos para el frontend
            $roles = $user->roles->pluck('nombre');
            $permisos = $user->roles->flatMap(function ($rol) {
                return $rol->permisos;
            })->pluck('slug')->unique();

            return response()->json([
                'success' => true,
                'message' => 'Login exitoso',
                'data' => [
                    'token' => $token,
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'roles' => $roles,
                        'permisos' => $permisos,
                    ],
                ],
            ]);
        }

        // Log de Intento fallido
        AuditLogger::log('LOGIN_FALLIDO', 'warning', "Intento de login fallido para email: {$request->email}");

        return response()->json([
            'success' => false,
            'message' => 'Credenciales incorrectas',
        ], 401);
    }

    /**
     * Cerrar sesión
     */
    public function logout(Request $request)
    {
        $user = $request->user();
        if ($user) {
            AuditLogger::log('LOGOUT', 'info', "Usuario {$user->email} cerró sesión.");
            $user->currentAccessToken()->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Sesión cerrada correctamente',
        ]);
    }

    /**
     * Obtener usuario autenticado
     */
    public function user(Request $request)
    {
        $user = $request->user();
        $roles = $user->roles->pluck('nombre');
        $permisos = $user->roles->flatMap(function ($rol) {
            return $rol->permisos;
        })->pluck('slug')->unique();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $roles,
                'permisos' => $permisos,
            ],
        ]);
    }
}
